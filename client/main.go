package main

import (
  "fmt"
  "os"
  "syscall"
  "time"
)

func main() {
  es, err := NewEventSource("http://localhost:8000/api/1/strand/stream")
  if err != nil {
    panic(err)
  }
  defer es.Close()

  var p *os.Process
  strandOn := false

  go func() {
    t := time.NewTicker(30 * time.Second)
    defer t.Stop()

    for {
      // ensure that the strand stays off
      <-t.C
      if !strandOn {
        pa := new(os.ProcAttr)
        pa.Files = []*os.File{os.Stdin, os.Stdout, os.Stderr}
        os.StartProcess("./lb2", []string{"./lb2", "/dev/null"}, pa)
      }
    }
  }()

  es.Handlers["off"] = func(ev *Event) error {
    if p != nil {
      p.Signal(syscall.SIGTERM)
      if _, err := p.Wait(); err != nil {
        return err
      }
      p = nil
    }

    strandOn = false
    return nil
  }

  es.Handlers["reprogram"] = func(ev *Event) error {
    if p != nil {
      p.Signal(syscall.SIGTERM)
      if _, err := p.Wait(); err != nil {
        return err
      }
      p = nil
    }

    read, write, err := os.Pipe()
    if err != nil {
      return err
    }

    pa := new(os.ProcAttr)
    pa.Files = []*os.File{read, os.Stdout, os.Stderr}
    p, err = os.StartProcess("/usr/bin/lightbridge", []string{"/usr/bin/lightbridge", "-"}, pa)
    if err != nil {
      return err
    }

    strandOn = true

    go func() {
      if _, err := write.Write(ev.Data); err != nil {
        fmt.Printf("background program write: %s\n", err)
      }
      if err := write.Close(); err != nil {
        fmt.Printf("background program write(end): %s\n", err)
      }
    }()

    return nil
  }

  if err := es.Loop(); err != nil {
    fmt.Printf("error: %s\n", err)
    os.Exit(1)
  }
}
