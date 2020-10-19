package main

import (
	"os"
	"syscall"
	"time"
)

type lightbridgeProcess struct {
	p *os.Process
}

func lightbridgeLaunch(programPath string) (*lightbridgeProcess, error) {
	p, err := os.StartProcess("./lb2", []string{"./lb2", programPath}, &os.ProcAttr{
		Files: []*os.File{os.Stdin, os.Stdout, os.Stderr},
	})

	if err != nil {
		return nil, err
	}
	t := time.Now()
	os.Chtimes(programPath, t, t)
	return &lightbridgeProcess{p}, nil
}

func (proc lightbridgeProcess) Exit() error {
	c := make(chan error)

	proc.p.Signal(syscall.SIGINT)
	go func() {
		_, err := proc.p.Wait()
		c <- err
	}()
	t := time.NewTimer(3 * time.Second)

	for {
		select {
		case err := <-c:
			t.Stop()
			return err

		case <-t.C:
			proc.p.Kill()
		}
	}
}
