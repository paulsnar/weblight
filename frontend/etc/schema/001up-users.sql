begin transaction;

  create table "users" (
    "id" integer primary key,
    "username",
    "password",
    "is_programmer",
    "is_controller",
    "is_supereditor"
  );

  insert into "users" values
    (1, "root", "", 1, 1, 1);

  create temporary table "programs_migr" as
    select * from "programs";
  drop table "programs";
  create table "programs" (
    "id" integer primary key,
    "ref",
    "revision",
    "author_id",
    "content",
    foreign key ("author_id") references "users" ("id")
  );
  create unique index "programs_ref_revision" on "programs" ("ref", "revision");

  insert into "programs"
    select
      "programs_migr"."id" as "id",
      "programs_migr"."ref" as "ref",
      "programs_migr"."revision" as "revision",
      1 as "author_id",
      "programs_migr"."content" as "content" from "programs_migr";

  drop table "programs_migr";

commit;
