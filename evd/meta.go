package main

import (
	"net/http"
	"sync"
)

type metaServer struct {
	m       sync.Mutex
	clients []*eventSource
}

func (meta *metaServer) HandleClient(w http.ResponseWriter, r *http.Request) {
	client, err := newBufferedEventSource(w, r)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	func() {
		meta.m.Lock()
		defer meta.m.Unlock()
		meta.clients = append(meta.clients, client)
	}()

	defer func() {
		meta.m.Lock()
		defer meta.m.Unlock()
		for i, _client := range meta.clients {
			if _client == client {
				meta.clients = append(meta.clients[:i], meta.clients[i+1:]...)
				return
			}
		}
	}()

	client.Loop()
}

func (meta *metaServer) Notify(event string) {
	meta.m.Lock()
	defer meta.m.Unlock()

	for _, client := range meta.clients {
		client.Events <- esEvent{Event: event}
	}
}

var meta = new(metaServer)
