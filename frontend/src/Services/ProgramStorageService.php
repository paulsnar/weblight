<?php declare(strict_types=1);

namespace PN\Weblight\Services;

use PN\Weblight\Auth\User;
use PN\Weblight\Data\{Database, DatabaseException};
use PN\Weblight\Data\Models\Program;
use PN\Weblight\Errors\{ConflictException, NotFoundException, SentinelMismatchException};
use function PN\Weblight\array_pick;

class ProgramStorageService
{
  /** @var Database */
  protected $db;

  public static function generateIdentifier(): string
  {
    static $_ALPHABET = '123456789abcdefghijkmnopqrstuvwxyz',
           $LENGTH = 8;
    $ALPHABET = str_split($_ALPHABET);

    $result = '';
    for ($i = 0; $i < $LENGTH; $i += 1) {
      $result .= array_pick($ALPHABET);
    }
    return $result;
  }

  public function __construct(Database $db)
  {
    $this->db = $db;
  }

  /** @return Program[] */
  public function getProgramStubList()
  {
    $programs = $this->db->selectAll(
      'select "id", "ref", max("revision") as "revision" from "programs" group by "ref"');
    return array_map([ Program::class, 'fromDatabaseRow' ], $programs);
  }

  public function getLatestRevision(string $id): int
  {
    $row = $this->db->selectOne(
      'select "revision" from "programs" where "ref" = ? order by "revision" desc limit 1',
      [ $id ]);
    if ($row === null) {
      throw new NotFoundException();
    }

    return intval($row['revision'], 10);
  }

  public function getLatestProgram(string $id): Program
  {
    $row = $this->db->selectOne(
      'select * from "programs" where "ref" = ? order by "revision" desc limit 1',
      [ $id ]);
    if ($row === null) {
      throw new NotFoundException();
    }

    return Program::fromDatabaseRow($row);
  }

  public function getProgram(string $id, int $revision): Program
  {
    $row = $this->db->selectOne(
      'select * from "programs" where "ref" = ? and "revision" = ?',
      [ $id, $revision ]);
    if ($row === null) {
      throw new NotFoundException();
    }

    return Program::fromDatabaseRow($row);
  }

  public function createProgram(User $author, string $content): Program
  {
    return $this->db->transaction(function () use ($author, $content) {
      $program = new Program();

      $i = 0;
      do {
        if ($i++ >= 10) {
          throw new ConflictException('No identifier could be found for Program');
        }
        $id = static::generateIdentifier();
        $conflict = $this->db->selectOne(
          'select count(1) as "conflict" from "programs" where "ref" = ? limit 1',
          [ $id ])['conflict'];
      } while ($conflict !== '0');
      $program->ref = $id;
      $program->revision = 1;
      $program->content = $content;

      $this->db->query(
        'insert into "programs" ("ref", "revision", "author_id", "content") ' .
          'values (?, ?, ?, ?)',
        [ $id, 1, $author->id, $content ]);
      $program->id = $this->db->lastInsertId();

      return $program;
    });
  }

  public function insertNewRevision(Program $program, string $newContent): Program
  {
    return $this->db->transaction(function () use ($program, $newContent) {
      $latestRevision = $this->db->selectOne(
        'select "revision" from "programs" where "ref" = ? order by "revision" desc limit 1',
        [ $program->ref ]);
      if ($latestRevision === null) {
        throw new NotFoundException();
      }

      $latestRevision = intval($latestRevision['revision'], 10);
      if ($program->revision !== $latestRevision) {
        throw new SentinelMismatchException($latestRevision);
      }
      $program->revision += 1;

      $this->db->query(
        'insert into "programs" ("ref", "revision", "author_id", "content") ' .
          'values (?, ?, ?, ?)',
        [ $program->ref, $program->revision, $program->authorId, $newContent ]);
      $program->id = $this->db->lastInsertId();

      return $program;
    });
  }
}
