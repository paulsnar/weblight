package main

import (
	"math"
	"time"
)

type exponentialBackoff struct {
	failures int
	min, max time.Duration
}

func newExponentialBackoff(min, max time.Duration) *exponentialBackoff {
	return &exponentialBackoff{-1, min, max}
}

func (e *exponentialBackoff) Fail() {
	e.failures += 1
	duration := e.min * time.Duration(math.Pow(2, float64(e.failures)))
	if duration > e.max {
		duration = e.max
	}
	time.Sleep(duration)
}

func (e *exponentialBackoff) Succeed() {
	e.failures = -1
}
