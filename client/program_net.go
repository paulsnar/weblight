package main

import (
  "bytes"
  "fmt"
  "net/http"
)

func FetchProgram(id ProgramID) (Program, error) {
  url := fmt.Sprintf("https://wl.xn--t-oha.lv/api/1/programs/%s?revision=%d",
    id.ID, id.Revision)
  resp, err := http.Get(url)
  if err != nil {
    return nil, err
  }
  defer resp.Body.Close()

  b := new(bytes.Buffer)
  if _, err := b.ReadFrom(resp.Body); err != nil {
    return nil, err
  }

  return Program(b.Bytes()), nil
}
