package main

import (
  "errors"
  "fmt"
  "net/http"
  "encoding/json"
  "os"
  "sync"
)

var (
  clientM = new(sync.Mutex)
  client *EventSource

  metaClientsM = new(sync.Mutex)
  metaClients []*EventSource

  ErrConflict = errors.New("conflict")
)

func metaNotify(event, data string) {
  metaClientsM.Lock()
  defer metaClientsM.Unlock()

  for _, client := range metaClients {
    client.Events <- Event{Event: event, Data: data}
  }
}

func handleClient(w http.ResponseWriter, r *http.Request) {
  _client, err := func() (*EventSource, error) {
    clientM.Lock()
    defer clientM.Unlock()

    if client != nil {
      w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
      w.WriteHeader(http.StatusConflict)
      w.Write([]byte("another event client is already connected\n"))
      return nil, ErrConflict
    }

    _client, err := NewEventSource(w, r)
    if err != nil {
      fmt.Printf("event source creation: %s\n", err)
      w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
      w.WriteHeader(http.StatusInternalServerError)
      w.Write([]byte("something went wrong\n"))
      return nil, err
    }

    client = _client

    return _client, nil
  }()
  if err != nil {
    return
  }

  defer func() {
    clientM.Lock()
    defer clientM.Unlock()
    client = nil
    metaNotify("disconnected", "")
  }()

  metaNotify("connected", "")

  if err := _client.Loop(); err != nil {
    fmt.Printf("event source loop: %s\n", err)
  }
}

func handleMetaClient(w http.ResponseWriter, r *http.Request) {
  _client, err := func() (*EventSource, error) {
    _client, err := NewBufferedEventSource(w, r)
    if err != nil {
      fmt.Printf("event source creation: %s\n", err)
      w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
      w.WriteHeader(http.StatusInternalServerError)
      w.Write([]byte("something went wrong\n"))
      return nil, err
    }

    metaClientsM.Lock()
    defer metaClientsM.Unlock()
    metaClients = append(metaClients, _client)

    return _client, nil
  }()
  if err != nil {
    return
  }

  w.Header().Set("Access-Control-Allow-Origin", "http://127.0.14.1:8001")

  defer func() {
    metaClientsM.Lock()
    defer metaClientsM.Unlock()

    for i, client := range metaClients {
      if client == _client {
        metaClients = append(metaClients[:i], metaClients[i+1:]...)
        return
      }
    }
  }()

  if err := _client.Loop(); err != nil {
    fmt.Printf("meta client loop: %s\n", err)
  }
}

func handleSubmission(w http.ResponseWriter, r *http.Request) {
  clientM.Lock()
  defer clientM.Unlock()

  if client == nil {
    w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
    w.WriteHeader(http.StatusNotFound)
    w.Write([]byte("no event consumer connected\n"))
    return
  }

  dec := json.NewDecoder(r.Body)
  var ev Event
  if err := dec.Decode(&ev); err != nil {
    fmt.Printf("event decoding: %s\n", err)
    w.Header().Set("Content-Type", "text/plain; charset=UTF-8")
    w.WriteHeader(http.StatusBadRequest)
    w.Write([]byte("could not parse request body\n"))
    return
  }

  client.Events <- ev
  w.WriteHeader(http.StatusCreated)
}

func handleConnectedCheck(w http.ResponseWriter, r *http.Request) {
  w.Header().Set("Content-Type", "application/json; charset=UTF-8")
  w.WriteHeader(http.StatusOK)
  if client == nil {
    w.Write([]byte("false"))
  } else {
    w.Write([]byte("true"))
  }
}

func _main() int {
  http.HandleFunc("/meta", handleMetaClient)
  http.HandleFunc("/submit", handleSubmission)
  http.HandleFunc("/consume", handleClient)
  http.HandleFunc("/connected", handleConnectedCheck)

  go fmt.Printf("listening on 127.0.14.1:8000\n")
  if err := http.ListenAndServe("127.0.14.1:8000", nil); err != nil {
    fmt.Printf("error: %s\n", err)
    return 1
  }
  return 0
}

func main() {
  if stat := _main(); stat != 0 {
    defer os.Exit(stat)
  }
}
