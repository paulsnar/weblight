begin transaction;

  create temporary table "programs_migr" as
    select * from "programs";
  drop table "programs";
  create table "programs" (
    "id" integer primary key,
    "ref",
    "revision",
    "content"
  );
  create unique index "programs_ref_revision" on "programs" ("ref", "revision");

  insert into "programs"
    select
      "programs_migr"."id" as "id",
      "programs_migr"."ref" as "ref",
      "programs_migr"."revision" as "revision",
      "programs_migr"."content" as "content" from "programs_migr";

  drop table "programs_migr";

  drop table "users";

commit;
