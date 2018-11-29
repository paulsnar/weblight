package main


type ProgramID struct {
  ID        string
  Revision  uint
}
func (pi ProgramID) IsEmpty() bool {
  return pi.ID != "" && pi.Revision > 0
}

type Program []byte

type ProgramCache struct {
  recents []ProgramID
  storage map[ProgramID]Program
}

func NewProgramCache() *ProgramCache {
  return &ProgramCache{
    recents: make([]ProgramID, 5),
    storage: make(map[ProgramID]Program, 5),
  }
}

func (pc *ProgramCache) Store(id ProgramID, prog Program) {
  if ! pc.recents[4].IsEmpty() {
    delete(pc.storage, pc.recents[4])
  }

  pc.recents[4] = pc.recents[3]
  pc.recents[3] = pc.recents[2]
  pc.recents[2] = pc.recents[1]
  pc.recents[1] = pc.recents[0]

  pc.recents[0] = id
  pc.storage[id] = prog
}

func (pc *ProgramCache) Recall(id ProgramID) Program {
  return pc.storage[id]
}
