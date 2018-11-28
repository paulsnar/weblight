begin transaction;

  create table "programs" (
    "id" integer primary key,
    "ref",
    "revision",
    "content"
  );
  create unique index "programs_ref_revision" on "programs" ("ref", "revision");

commit;
