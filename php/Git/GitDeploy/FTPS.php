<?php namespace Surikat\Git\GitDeploy;
class FTPS extends FTP{
    function getConnection($server){
		return @ftp_ssl_connect($server['host'], $server['port'], 30);
	}
}