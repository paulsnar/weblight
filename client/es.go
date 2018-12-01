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
  Retry int
}

func esSplit(data []byte, atEOF bool) (advance int, token []byte, err error) {
  l := 2
  brk := bytes.Index(data, []byte("\r\n"))
  if brk == -1 {
    l = 1
    brk = bytes.Index(data, []byte("\n"))
  }
  if brk == -1 {
    // this is the oddest of all
    l = 1
    brk = bytes.Index(data, []byte("\r"))
  }
  if brk == -1 {
    return 0, nil, nil
  }

  body := data[:brk]
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
  // see: https://html.spec.whatwg.org/multipage/server-sent-events.html#event-stream-interpretation
  // adapted for compatible semantics, if not implementation

start:

  e := new(Event)
  sc := es.sc

  for {
    if !sc.Scan() {
      return nil, sc.Err()
    }
    line := sc.Text()
    if line == "" {
      break // dispatch event
    }

    if line[0] == ':' { // comment line
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
        e.Event = fieldValue

      case "data":
        if e.Data == nil {
          e.Data = []byte(fieldValue + "\n")
        } else {
          e.Data = append(e.Data, []byte(fieldValue + "\n")...)
        }

      case "id":
        if !strings.ContainsRune(fieldValue, 0) {
          e.ID = fieldValue
        }

      case "retry":
        if i, err := strconv.ParseInt(fieldValue, 10, 64); err == nil {
          e.Retry = int(i)
        }
    }
  }

  if e.Data == nil {
    goto start // get next event instead
  }
  e.Data = bytes.TrimSuffix(e.Data, []byte{'\n'})

  if e.Event == "" {
    e.Event = "message"
  }

  return e, nil
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
