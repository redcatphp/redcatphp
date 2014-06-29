-- http://www.siafoo.net/snippet/148
CREATE OR REPLACE FUNCTION strip_tags(TEXT) RETURNS TEXT AS $$
	SELECT regexp_replace(regexp_replace($1, E'(?x)<[^>]*?(\s alt \s* = \s* ([\'"]) ([^>]*?) \2) [^>]*? >', E'\3'), E'(?x)(< [^>]*? >)', '', 'g')
$$ LANGUAGE SQL;
