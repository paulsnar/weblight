package main

import (
  "fmt"
  "os"
  "sort"
)

type fileInfos []os.FileInfo
func (fi *fileInfos) Len() int {
  return len(*fi)
}
func (fi *fileInfos) Less(i, j int) bool {
  return (*fi)[i].ModTime().UnixNano() < (*fi)[j].ModTime().UnixNano()
}
func (fi *fileInfos) Swap(i, j int) {
  (*fi)[i], (*fi)[j] = (*fi)[j], (*fi)[i]
}

func cacheFindLastProgram() string {
  dir, err := os.Open(ProgramRoot)
  if err != nil {
    fmt.Printf("warning: could not open cache directory: %s\n", err)
    return ""
  }
  defer dir.Close()

  entries, err := dir.Readdir(-1)
  if err != nil {
    fmt.Printf("warning: could not list cache directory: %s\n", err)
    return ""
  }
  if len(entries) == 0 {
    return ""
  }

  entriesSorter := fileInfos(entries)
  sort.Sort(&entriesSorter)
  lastProgram := entriesSorter[len(entriesSorter)-1]

  return ProgramRoot + "/" + lastProgram.Name()
}

func cacheHasProgram(id ProgramID) bool {
  path := ProgramRoot + "/" + id.Path()
  _, err := os.Stat(path)
  return err == nil
}

func cacheStoreProgram(id ProgramID, prog []byte) (string, error) {
  path := ProgramRoot + "/" + id.Path()

  f, err := os.OpenFile(path, os.O_WRONLY | os.O_CREATE | os.O_TRUNC, 0644)
  if err != nil {
    return "", err
  }
  defer f.Close()

  if _, err := f.Write(prog); err != nil {
    return "", err
  }

  return path, nil
}
