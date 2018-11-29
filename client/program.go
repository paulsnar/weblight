package main

import "fmt"

// const ProgramRoot = "/var/weblight/programs"
const ProgramRoot = "./programs"

type ProgramID struct {
  ID        string
  Revision  uint
}
func (pi ProgramID) IsEmpty() bool {
  return pi.ID != "" && pi.Revision > 0
}
func (pi ProgramID) String() string {
  return fmt.Sprintf("%s-%d", pi.ID, pi.Revision)
}
func (pi ProgramID) Path() string {
  return pi.String() + ".lua"
}
func (pi ProgramID) FullPath() string {
  return ProgramRoot + "/" + pi.Path()
}

