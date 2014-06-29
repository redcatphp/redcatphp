-- usage: SELECT strip_tags_filter('<p>test <script>alert(1);</script> <strong>strong</strong> <div>div</div></p>', 'p,strong');

CREATE OR REPLACE FUNCTION strip_tags(in_text text, in_allowed text)
  RETURNS text AS
$BODY$
DECLARE
   m record;
   v_matches text[];
   v_allowed text[] := (CASE WHEN in_allowed IS NOT NULL THEN string_to_array(in_allowed, ',') ELSE ARRAY[]::text[] END);
   v_result text := in_text;
BEGIN
   FOR m IN SELECT regexp_matches($1, E'(</?([a-z0-9_\-]+) *[^>]*>)', 'g') i LOOP
      IF (m.i[2] = ANY(v_allowed)) = FALSE THEN
         v_result := replace(v_result, m.i[1], '');
      END IF;
   END LOOP;
   RETURN v_result;
END;
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;
