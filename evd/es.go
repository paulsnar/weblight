package main

import (
	"context"
	"errors"
	"net/http"
	"strconv"
	"strings"
	"time"
)

type esEvent struct {
	ID    string `json:"id"`
	Event string `json:"event"`
	Data  string `json:"data"`
	Retry int
}

type eventSourceConnection interface {
	http.ResponseWriter
	http.Flusher
}

type eventSource struct {
	Events chan esEvent
	c      eventSourceConnection
	ctx    context.Context
}

var errESIncompatibleConnection = errors.New("eventsource: incompatible connection")

func newEventSource(rw http.ResponseWriter, rq *http.Request) (*eventSource, error) {
	c, ok := rw.(eventSourceConnection)
	if !ok {
		return nil, errESIncompatibleConnection
	}

	return &eventSource{
		Events: make(chan esEvent),
		c:      c,
		ctx:    rq.Context(),
	}, nil
}

func newBufferedEventSource(rw http.ResponseWriter, rq *http.Request) (*eventSource, error) {
	es, err := newEventSource(rw, rq)
	if err != nil {
		return nil, err
	}

	close(es.Events)
	es.Events = make(chan esEvent, 3)
	return es, nil
}

func (es *eventSource) Loop() error {
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
			if _, err := c.Write([]byte("\n")); err != nil {
				return err
			}
			c.Flush()

		case ev := <-es.Events:
			msg := make([]byte, 0)

			if ev.Event != "" {
				event := strings.Replace(ev.Event, "\n", "\nevent: ", -1)
				msg = append(msg, []byte("event: "+event+"\n")...)
			}
			if ev.ID != "" {
				id := strings.Replace(ev.ID, "\n", "\nid: ", -1)
				msg = append(msg, []byte("id: "+id+"\n")...)
			}
			if ev.Retry != 0 {
				retry := strconv.FormatInt(int64(ev.Retry), 10)
				msg = append(msg, []byte("retry: "+retry+"\n")...)
			}
			if ev.Data != "" {
				msg = append(msg, []byte("data: ")...)
				data := strings.Replace(ev.Data, "\n", "\ndata: ", -1)
				msg = append(msg, []byte(data)...)
				msg = append(msg, '\n')
			} else {
				msg = append(msg, []byte("data\n")...)
			}

			msg = append(msg, '\n')
			if _, err := c.Write(msg); err != nil {
				return err
			}
			c.Flush()
		}
	}
}
