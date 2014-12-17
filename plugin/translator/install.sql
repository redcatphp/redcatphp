CREATE TABLE "catalogue" (
	"id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	"name" text NOT NULL
);

CREATE TABLE "message" (
	"id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	"catalogue_id" integer NOT NULL,
	"msgid" text NOT NULL,
	"msgstr" text NOT NULL,
	"comments" text,
	"extractedComments" text,
	"reference" text,
	"flags" text,
	"isObsolete" integer,
	"previousUntranslatedString" text,
	"updatedAt" NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY ("catalogue_id") REFERENCES "catalogue" ("id")
);
