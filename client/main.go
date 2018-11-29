package main

import (
  "fmt"
  "os"
  "strconv"
  "strings"
  "syscall"
  "time"
)

func main() {
  es, err := NewEventSource("https://wl.xn--t-oha.lv/api/1-realtime/strand")
  if err != nil {
    panic(err)
  }
  defer es.Close()

  var p *os.Process
  strandOn := false

  cache := NewProgramCache()

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

  es.Handlers["launch"] = func(ev *Event) error {
    programSpecifier := strings.Split(string(ev.Data), "-")
    if len(programSpecifier) != 2 {
      return fmt.Errorf("invalid program specifier: %s", ev.Data)
    }
    rev, err := strconv.ParseUint(programSpecifier[1], 10, 64)
    if err != nil {
      return err
    }

    programId := ProgramID{programSpecifier[0], uint(rev)}

    program := cache.Recall(programId)
    if program == nil {
      program, err = FetchProgram(programId)
      if err != nil {
        return err
      }
    }

    if p != nil {
      if err := ExitLightbridge(p); err != nil {
        return err
      }
      p = nil
    }

    read, write, err := os.Pipe()
    if err != nil {
      return err
    }

    p, err = LaunchLightbridge(read, os.Stdout, os.Stderr)
    if err != nil {
      return err
    }

    strandOn = true
    go func() {
      if _, err := write.Write([]byte(program)); err != nil {
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
