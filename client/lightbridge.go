package main

import (
  "os"
  "syscall"
  "time"
)

func awaitProcessExit(p *os.Process, c chan<- error) {
  _, err := p.Wait()
  c <- err
  close(c)
}

func ExitLightbridge(p *os.Process) error {
  c := make(chan error)

  p.Signal(syscall.SIGTERM)
  go awaitProcessExit(p, (chan<- error)(c))
  t := time.NewTimer(2 * time.Second)

  for {
    select {
      case err := <-c:
        t.Stop()
        return err

      case <-t.C:
        p.Kill()
    }
  }
}

func LaunchLightbridge(programPath string) (p *os.Process, err error) {
  p, err = os.StartProcess("./lb2", []string{"./lb2", programPath}, &os.ProcAttr{
    Files: []*os.File{os.Stdin, os.Stdout, os.Stderr},
  })

  go func() {
    // refresh last-modified timestamp
    t := time.Now()
    os.Chtimes(programPath, t, t)
  }()

  return
}
