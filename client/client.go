package main

import (
	"bytes"
	"encoding/binary"
	"errors"
	"io"
	"net"
)

type clientConnection struct {
	conn net.Conn
}

var clientInitMagic = []byte("weblight")
var errClientInitMagicMismatch = errors.New("weblight client: init magic mismatch")

func clientConnect(host string) (*clientConnection, error) {
	conn, err := net.Dial("tcp", host+":5448")
	if err != nil {
		return nil, err
	}
	defer func() {
		if conn != nil {
			conn.Close()
		}
	}()

	init := make([]byte, len(clientInitMagic))
	if _, err := io.ReadFull(conn, init); err != nil {
		return nil, err
	} else if !bytes.Equal(init, clientInitMagic) {
		return nil, errClientInitMagicMismatch
	}

	conn.Write(clientInitMagic)

	client := &clientConnection{conn}
	conn = nil
	return client, nil
}

type clientEventType int

const (
	clientEventNoop    clientEventType = 0
	clientEventRun                     = 1
	clientEventStop                    = 2
	clientEventRunLast                 = 3
)

func (t clientEventType) IsValid() bool {
	return t >= clientEventNoop && t <= clientEventRunLast
}

var errClientShort = errors.New("weblight client: short read")
var errClientFormat = errors.New("weblight client: bad packet format")

type clientEvent struct {
	Type    clientEventType
	Program programId
}

func (c *clientConnection) ReadNextEvent() (*clientEvent, error) {
	buf := make([]byte, 16)
	if _, err := io.ReadFull(c.conn, buf[:4]); err != nil {
		return nil, err
	}

	eventType := clientEventType(binary.BigEndian.Uint32(buf[:4]))
	if !eventType.IsValid() {
		return nil, errClientFormat
	}
	event := &clientEvent{Type: eventType}

	if eventType == clientEventRun {
		if _, err := io.ReadFull(c.conn, buf[4:16]); err != nil {
			return nil, err
		}

		event.Program = programId{
			ID:       string(buf[4:12]),
			Revision: binary.BigEndian.Uint32(buf[12:16]),
		}
	}

	return event, nil
}

func (c *clientConnection) Close() {
	if c.conn != nil {
		c.conn.Close()
		c.conn = nil
	}
}
