package main

import (
	"bytes"
	"fmt"
	"net/http"
	"time"
)

var programRoot = "./programs"

type programId struct {
	ID       string
	Revision uint32
}

func (id *programId) IsEmpty() bool {
	return id.ID == "" && id.Revision == 0
}
func (id *programId) String() string {
	return fmt.Sprintf("%s.%d", id.ID, id.Revision)
}
func (id *programId) Path() string {
	return id.String() + ".lua"
}

func fetchProgram(id programId) ([]byte, error) {
	url := fmt.Sprintf("http://weblight/api/1/programs/%s?revision=%d", id.ID, id.Revision)
	resp, err := http.Get(url)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	b := new(bytes.Buffer)
	if _, err := b.ReadFrom(resp.Body); err != nil {
		return nil, err
	}

	return b.Bytes(), nil
}

func clientReader(c chan<- *clientEvent) {
	b := newExponentialBackoff(20*time.Millisecond, 10*time.Second)

	loop := func() (stop bool) {
		defer func() {
			// might panic on send when c is closed
			// but that's okay
			if p := recover(); p != nil {
				stop = true
			}
		}()

		client, err := clientConnect("weblight")
		if err != nil {
			fmt.Printf("warning: couldn't connect: %s, retrying soon\n", err)
			b.Fail()
			return
		}
		b.Succeed()

		for {
			event, err := client.ReadNextEvent()
			if err != nil {
				fmt.Printf("warning: couldn't read event: %s, reconnecting\n", err)
				b.Fail()
				return
			}

			c <- event
		}
	}

	for loop() {
	}
}

func turnerOffer(c <-chan bool) {
	var t *time.Ticker
	var tc <-chan time.Time

	for {
		select {
		case enable, ok := <-c:
			if !ok {
				return
			}

			if t != nil {
				t.Stop()
				t = nil
				tc = nil
			}
			if enable {
				t := time.NewTicker(30 * time.Second)
				tc = t.C
			}

		case <-tc:
			proc, err := lightbridgeLaunch("/dev/null")
			if err != nil {
				fmt.Printf("warning: failed to clear: %s\n", err)
			} else {
				proc.p.Wait()
			}
		}
	}
}

func runLast() (*lightbridgeProcess, error) {
	prog := cacheFindLastProgram()
	if prog == "" {
		fmt.Printf("warning: requested run last but no program found\n")
		return nil, nil
	}

	proc, err := lightbridgeLaunch(prog)
	return proc, err
}

func run(id programId) (*lightbridgeProcess, error) {
	if cacheHasProgram(id) {
		proc, err := lightbridgeLaunch(programRoot + "/" + id.Path())
		return proc, err
	}

	prog, err := fetchProgram(id)
	if err != nil {
		return nil, err
	}

	path, err := cacheStoreProgram(id, prog)
	if err != nil {
		return nil, err
	}

	proc, err := lightbridgeLaunch(path)
	return proc, err
}

func main() {
	events := make(chan *clientEvent)
	turnerOfferEnable := make(chan bool)

	go clientReader((chan<- *clientEvent)(events))
	go turnerOffer((<-chan bool)(turnerOfferEnable))

	var proc *lightbridgeProcess
	var err error

	turnerOfferEnable <- true

	procExit := func() {
		if proc == nil {
			return
		}
		if err := proc.Exit(); err != nil {
			fmt.Printf("warning: failed to exit program: %s\n", err)
			proc.p.Kill()
		}
		proc = nil
	}

	for {
		select {
		case event := <-events:
			switch event.Type {
			case clientEventRunLast:
				turnerOfferEnable <- false
				procExit()
				proc, err = runLast()
				if err != nil {
					fmt.Printf("warning: failed to run last: %s\n", err)
					turnerOfferEnable <- true
				}

			case clientEventRun:
				turnerOfferEnable <- false
				procExit()
				proc, err = run(event.Program)
				if err != nil {
					fmt.Printf("warning: failed to run %s: %s\n", event.Program, err)
					turnerOfferEnable <- true
				}

			case clientEventStop:
				procExit()
				turnerOfferEnable <- true

			case clientEventNoop:
				// do nothing…
			}
		}
	}
}
