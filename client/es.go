package main

import (
  "bufio"
  "bytes"
  "fmt"
  "net/http"
  "mime"
  "strconv"
  "strings"
)

type esErr struct {
  resp *http.Response
}

type ErrInvalidMimetype esErr
func (e *ErrInvalidMimetype) Error() string {
  return "eventsource: invalid response type"
}

type ErrRequestFailed esErr
func (e *ErrRequestFailed) Error() string {
  return fmt.Sprintf("eventsource: non-200 response code (%d)", e.resp.StatusCode)
}

type EventHandler func(ev *Event) error

type EventSource struct {
  resp *http.Response
  sc *bufio.Scanner
  Handlers map[string]EventHandler
}

type Event struct {
  ID, Event string
  Data []byte
  Retry *int
}

func esSplit(data []byte, atEOF bool) (advance int, token []byte, err error) {
  l := 2
  brk := bytes.Index(data, []byte("\n\n"))
  if brk == -1 {
    l = 4
    brk = bytes.Index(data, []byte("\r\n\r\n"))
  }
  if brk == -1 {
    // this is the oddest of all
    l = 2
    brk = bytes.Index(data, []byte("\r\r"))
  }
  if brk == -1 {
    return 0, nil, nil
  }

  body := data[:brk + l]
  return brk + l, body, nil
}

func NewEventSource(url string) (*EventSource, error) {
  resp, err := http.Get(url)
  if err != nil {
    return nil, err
  }

  if resp.StatusCode != 200 {
    return nil, &ErrRequestFailed{resp}
  }

  mimetype, _, err := mime.ParseMediaType(resp.Header.Get("Content-Type"))
  if err != nil {
    return nil, err
  }

  if mimetype != "text/event-stream" {
    return nil, &ErrInvalidMimetype{resp}
  }


  scanner := bufio.NewScanner(resp.Body)
  scanner.Split(esSplit)

  return &EventSource{resp, scanner, make(map[string]EventHandler)}, nil
}

func (es *EventSource) Close() {
  es.sc = nil
  es.resp.Body.Close()
}

func (es *EventSource) GetNextEvent() (*Event, error) {
  if !es.sc.Scan() {
    return nil, es.sc.Err()
  }

  msg := string(es.sc.Bytes())
  lineEnding := msg[len(msg)-2:]
  if lineEnding != "\r\n" {
    lineEnding = lineEnding[1:]
  }

  ev := new(Event)
  lines := strings.Split(msg, lineEnding)

  for _, line := range lines {
    if line == "" {
      // last line
      break
    }
    if line[0] == ':' {
      // comment
      continue
    }
    parts := strings.SplitN(line, ":", 2)
    if len(parts) == 1 {
      parts = append(parts, "")
    } else if parts[1][0] == ' ' {
      parts[1] = parts[1][1:]
    }

    fieldName := parts[0]
    fieldValue := parts[1]
    switch fieldName {
      case "event":
        ev.Event = fieldValue

      case "data":
        if ev.Data == nil {
          ev.Data = []byte(fieldValue + "\n")
        } else {
          ev.Data = append(ev.Data, []byte(fieldValue + "\n")...)
        }

      case "id":
        if !strings.ContainsRune(fieldValue, 0) {
          ev.ID = fieldValue
        }

      case "retry":
        if i, err := strconv.ParseInt(fieldValue, 10, 64); err == nil {
          _i := int(i)
          ev.Retry = &_i
        }
    }
  }

  if ev.Event == "" {
    ev.Event = "message"
  }

  ev.Data = bytes.TrimSuffix(ev.Data, []byte("\n"))
  return ev, nil
}

func (es *EventSource) Loop() error {
  for {
    ev, err := es.GetNextEvent()
    if ev == nil && err == nil {
      break
    }

    if handler := es.Handlers[ev.Event]; handler != nil {
      if err := handler(ev); err != nil {
        es.Close()
        return err
      }
    }
  }

  return nil
}
