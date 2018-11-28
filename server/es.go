package main

import (
  "bytes"
  "context"
  "errors"
  "net/http"
  "strconv"
  "strings"
  "time"
)

type Event struct {
  ID, Event string
  Data []byte
  Retry *int
}

type eventSourceConnection interface {
  http.ResponseWriter
  http.Flusher
}

type EventSource struct {
  Events chan Event
  c eventSourceConnection
  ctx context.Context
}

var ErrESIncompatibleConnection = errors.New("eventsource: incompatible connection")

func NewEventSource(rw http.ResponseWriter, rq *http.Request) (*EventSource, error) {
  c, ok := rw.(eventSourceConnection)
  if !ok {
    return nil, ErrESIncompatibleConnection
  }

  return &EventSource{
    Events: make(chan Event),
    c: c,
    ctx: rq.Context(),
  }, nil
}

func (es *EventSource) Loop() (error) {
  defer close(es.Events)

  c := es.c

  h := c.Header()
  h.Set("Content-Type", "text/event-stream")
  c.WriteHeader(http.StatusOK)
  c.Write([]byte(":\n"))
  c.Flush()

  t := time.NewTicker(15 * time.Second)
  defer t.Stop()

  for {
    select {
      case <-es.ctx.Done():
        return nil

      case <-t.C:
        if _, err := c.Write([]byte(":\n")); err != nil {
          return err
        }
        c.Flush()

      case ev := <-es.Events:
        msg := make([]byte, 0)

        if ev.Event != "" {
          event := strings.Replace(ev.Event, "\n", "\nevent: ", -1)
          msg = append(msg, []byte("event: " + event + "\n")...)
        }
        if ev.ID != "" {
          id := strings.Replace(ev.ID, "\n", "\nid: ", -1)
          msg = append(msg, []byte("id: " + id + "\n")...)
        }
        if ev.Retry != nil {
          retry := strconv.FormatInt(int64(*ev.Retry), 10)
          msg = append(msg, []byte("retry: " + retry + "\n")...)
        }
        if ev.Data != nil {
          msg = append(msg, []byte("data: ")...)
          data := bytes.Replace(ev.Data, []byte("\n"), []byte("\ndata: "), -1)
          msg = append(msg, data...)
          msg = append(msg, '\n')
        }

        msg = append(msg, '\n')
        if _, err := c.Write(msg); err != nil {
          return err
        }
        c.Flush()
    }
  }
}
