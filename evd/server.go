package main

import (
	"bytes"
	"encoding/binary"
	"errors"
	"io"
	"net"
	"time"
)

var clientInitMagic = []byte("weblight")
var errClientInitMagicMismatch = errors.New("client: init magic mismatch")

type eventType int

const (
	eventNoop    eventType = 0
	eventRun               = 1
	eventStop              = 2
	eventRunLast           = 3
)

type event struct {
	Type    eventType
	Program programId
}

type client struct {
	conn   net.Conn
	events chan *event
	dead   chan<- *client
}

func (c *client) eofLoop(ender chan struct{}) {
	buf := []byte{0}
	for {
		if _, err := c.conn.Read(buf[:]); err != nil {
			close(ender)
			return
		}
	}
}

func (c *client) loop() {
	t := time.NewTicker(30 * time.Second)
	buf := make([]byte, 16)
	ender := make(chan struct{})
	go c.eofLoop(ender)

	defer func() {
		t.Stop()
		c.conn.Close()
		c.dead <- c
	}()

	for {
		select {
		case <-ender:
			return

		case <-t.C:
			binary.BigEndian.PutUint32(buf[:4], 0)
			if _, err := c.conn.Write(buf[:4]); err != nil {
				return
			}

		case ev, ok := <-c.events:
			if !ok {
				return
			}
			write := buf
			binary.BigEndian.PutUint32(write[:4], uint32(ev.Type))
			if !ev.Program.IsEmpty() {
				copy(write[4:12], ev.Program.ID)
				binary.BigEndian.PutUint32(write[12:16], ev.Program.Revision)
			} else {
				write = write[:4]
			}
			if _, err := c.conn.Write(write); err != nil {
				return
			}
		}
	}
}

type clientServer struct {
	listener    net.Listener
	clients     []*client
	events      chan *event
	connects    chan *client
	disconnects chan *client
}

func (s *clientServer) listen() {
	for {
		conn, err := s.listener.Accept()
		if err != nil {
			close(s.connects)
			s.listener = nil
		}

		conn.Write(clientInitMagic)
		init := make([]byte, 8)
		if _, err := io.ReadFull(conn, init); err != nil {
			conn.Close()
			continue
		} else if !bytes.Equal(init, clientInitMagic) {
			conn.Close()
			continue
		}

		events := make(chan *event)
		cl := &client{conn, events, (chan<- *client)(s.disconnects)}
		s.connects <- cl
	}
}

func (s *clientServer) loop() {
	for {
		select {
		case event, ok := <-s.events:
			if !ok {
				s.listener.Close()
				for _, client := range s.clients {
					close(client.events)
				}
				for _ = range s.clients {
					<-s.disconnects
				}
				close(s.disconnects)
				return
			}
			for _, client := range s.clients {
				client.events <- event
			}

		case client := <-s.connects:
			go client.loop()
			s.clients = append(s.clients, client)
			if len(s.clients) == 1 {
				meta.Notify("connected")
			}

		case client := <-s.disconnects:
			for i, _client := range s.clients {
				if _client == client {
					s.clients = append(s.clients[:i], s.clients[i+1:]...)
					break
				}
			}
			if len(s.clients) == 0 {
				meta.Notify("disconnected")
			}
		}
	}
}

func initServer(addr string) (*clientServer, error) {
	listener, err := net.Listen("tcp", addr+":5448")
	if err != nil {
		return nil, err
	}

	serv := &clientServer{
		listener:    listener,
		events:      make(chan *event),
		connects:    make(chan *client),
		disconnects: make(chan *client),
	}
	go serv.loop()
	go serv.listen()
	return serv, nil
}
