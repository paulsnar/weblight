package main

import (
  "fmt"
  "os"
  "strconv"
  "strings"
  "time"
)

func main() {
  // es, err := NewEventSource("https://wl.xn--t-oha.lv/api/1-realtime/strand")
  es, err := NewEventSource("http://127.0.14.1:8000/consume")
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
        p, _ := LaunchLightbridge("/dev/null")
        if p != nil {
          p.Wait()
        }
      }
    }
  }()

  es.Handlers["off"] = func(ev *Event) error {
    if p != nil {
      if err := ExitLightbridge(p); err != nil {
        return err
      }
      p = nil
    }

    strandOn = false
    return nil
  }

  es.Handlers["launch-last"] = func(ev *Event) error {
    if p != nil {
      if err := ExitLightbridge(p); err != nil {
        return err
      }
      p = nil
    }

    program := cacheFindLastProgram()
    if program == "" {
      // should report failure?
      return nil
    }

    var err error
    p, err = LaunchLightbridge(program)
    if err != nil {
      return err
    }

    strandOn = true
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

    if cacheHasProgram(programId) {
      p, err = LaunchLightbridge(programId.FullPath())
      if err != nil {
        return err
      }

      return nil
    }

    program, err := FetchProgram(programId)
    if err != nil {
      return err
    }
    programPath, err := cacheStoreProgram(programId, program)
    if err != nil {
      return err
    }

    if p != nil {
      if err := ExitLightbridge(p); err != nil {
        return err
      }
      p = nil
    }

    p, err = LaunchLightbridge(programPath)
    if err != nil {
      return err
    }

    strandOn = true
    return nil
  }

  if err := es.Loop(); err != nil {
    fmt.Printf("error: %s\n", err)
    os.Exit(1)
  }
}

