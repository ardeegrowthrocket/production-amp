<?php

class MagicToolbox_Sirv_Model_Adapter_S3_Wrapper
{
    protected $key;

    protected $secret;

    protected $host;

    protected $bucket;

    protected $date;

    protected $curlInfo;

    public function __construct($params)
    {
        $this->key    = $params['key'];
        $this->secret = $params['secret'];
        $this->host   = isset($params['host']) ? $params['host'] : 's3.sirv.com';
        $this->bucket = $params['bucket'];
        $this->date   = gmdate('D, d M Y H:i:s T');
    }

    public function listBuckets()
    {
        $buckets = array();
        $request = array('verb' => 'GET', 'resource' => '/');
        $result = $this->sendRequest($request);
        $xml = simplexml_load_string($result);
        if ($xml !== false && isset($xml->Buckets->Bucket)) {
            foreach ($xml->Buckets as $item) {
                $buckets[] = (string)$item->Bucket->Name;
            }
        }
        return $buckets;
    }

    public function getProfiles()
    {
        static $profiles = null;
        if ($profiles === null) {
            $profiles = array();
            $request = array(
                'verb' => 'GET',
                'bucket' => $this->bucket,
                'resource' => '/?prefix=Profiles/',
            );
            $result = $this->sendRequest($request);
            $xml = simplexml_load_string($result);
            if ($xml !== false && isset($xml->Contents)) {
                foreach ($xml->Contents as $contents) {
                    $key = (string)$contents->Key;
                    if (preg_match('#^Profiles/([^/]+)\.profile$#', $key, $matches)) {
                        $profiles[] = $matches[1];
                    }
                }
            }
        }
        return $profiles;
    }

    public function uploadFile($sirvPath, $fsPath, $webAccessible = false, $headers = null)
    {
        $request = array(
            'verb' => 'PUT',
            'bucket' => $this->bucket,
            'resource' => "{$sirvPath}",
            'content-md5' => $this->base64(md5_file($fsPath))
        );

        $fh = fopen($fsPath, 'r');

        $curl_opts = array(
            'CURLOPT_PUT' => true,
            'CURLOPT_INFILE' => $fh,
            'CURLOPT_INFILESIZE' => filesize($fsPath),
            'CURLOPT_CUSTOMREQUEST' => 'PUT'
        );

        if ($headers === null) {
            $headers = array();
        }

        $headers['Content-MD5'] = $request['content-md5'];

        if ($webAccessible === true && !isset($headers['x-amz-acl'])) {
            $headers['x-amz-acl'] = 'public-read';
        }

        if (!isset($headers['Content-Type'])) {
            $ext = pathinfo($fsPath, PATHINFO_EXTENSION);
            $headers['Content-Type'] = isset($this->mimeTypes[$ext]) ? $this->mimeTypes[$ext] : 'application/octet-stream';
        }
        $request['content-type'] = $headers['Content-Type'];

        $result = $this->sendRequest($request, $headers, $curl_opts);
        fclose($fh);
        return $this->curlInfo['http_code'] == '200';
    }

    public function doesObjectExist($sirvPath)
    {
        $request = array(
            'verb' => 'HEAD',
            'bucket' => $this->bucket,
            'resource' => "{$sirvPath}",
        );

        $curl_opts = array(
            'CURLOPT_NOBODY' => true,
        );

        $result = $this->sendRequest($request, null, $curl_opts);

        return $this->curlInfo['http_code'] == '200';
    }

    public function deleteObject($sirvPath)
    {
        $request = array(
            'verb' => 'DELETE',
            'bucket' => $this->bucket,
            'resource' => "{$sirvPath}",
        );
        $result = $this->sendRequest($request);
        return $this->curlInfo['http_code'] == '204';
    }

    public function deleteMultipleObjects($keys)
    {
        $contents = '<' . '?xml version="1.0"?>' . "\n" . '<Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Object><Key>' . implode('</Key></Object><Object><Key>', $keys) . '</Key></Object><Quiet>true</Quiet></Delete>' . "\n";

        $contentMd5 = base64_encode(md5($contents, true));

        $request = array(
            'verb' => 'POST',
            'bucket' => $this->bucket,
            'resource' => "/?delete",
            'content-md5' => $contentMd5,
            'content-type' => "application/xml",
        );

        $filesize = strlen($contents);
        $fh = fopen('php://temp', 'wb+');
        fwrite($fh, $contents);
        rewind($fh);

        $curl_opts = array(
            'CURLOPT_CUSTOMREQUEST' => 'POST',
            'CURLOPT_UPLOAD' => TRUE,
            'CURLOPT_INFILE' => $fh,
            'CURLOPT_INFILESIZE' => $filesize,
        );

        $headers = array(
            'Content-Type' => 'application/xml',
            'Content-MD5' => $contentMd5,
        );

        $result = $this->sendRequest($request, $headers, $curl_opts);
        fclose($fh);
        return $this->curlInfo['http_code'] == '200';
    }

    protected function sendRequest($request, $headers = null, $curl_opts = null)
    {
        if ($headers === null) {
            $headers = array();
        }

        $headers['Date'] = $this->date;
        $headers['Authorization'] = 'AWS ' . $this->key . ':' . $this->signature($request, $headers);
        foreach ($headers as $k => $v) {
            $headers[$k] = "$k: $v";
        }

        $host = isset($request['bucket']) ? $request['bucket'] . '.' . $this->host : $this->host;

        $uri = 'http://' . $host . $request['resource'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request['verb']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (is_array($curl_opts)) {
            foreach ($curl_opts as $k => $v) {
                curl_setopt($ch, constant($k), $v);
            }
        }

        $result = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);

        curl_close($ch);
        return $result;
    }

    protected function signature($request, $headers = null)
    {
        if ($headers === null) {
            $headers = array();
        }

        $canonicalizedAmzHeadersArr = array();
        $canonicalizedAmzHeadersStr = '';
        foreach ($headers as $k => $v) {
            $k = strtolower($k);

            if (substr($k, 0, 5) != 'x-amz') {
                continue;
            }

            if (isset($canonicalizedAmzHeadersArr[$k])) {
                $canonicalizedAmzHeadersArr[$k] .= ',' . trim($v);
            } else {
                $canonicalizedAmzHeadersArr[$k] = trim($v);
            }
        }
        ksort($canonicalizedAmzHeadersArr);

        foreach ($canonicalizedAmzHeadersArr as $k => $v) {
            $canonicalizedAmzHeadersStr .= "$k:$v\n";
        }

        if (isset($request['bucket'])) {
            $request['resource'] = '/' . $request['bucket'] . $request['resource'];
        }

        $str  = $request['verb'] . "\n";
        $str .= isset($request['content-md5']) ? $request['content-md5'] . "\n" : "\n";
        $str .= isset($request['content-type']) ? $request['content-type'] . "\n" : "\n";
        $str .= isset($request['date']) ? $request['date'] . "\n" : $this->date . "\n";
        $str .= $canonicalizedAmzHeadersStr.preg_replace('#\?(?!delete$).*$#is', '', $request['resource']);

        $sha1 = $this->hasher($str);
        return $this->base64($sha1);
    }

    //NOTE: Algorithm adapted (stolen) from http://pear.php.net/package/Crypt_HMAC/
    protected function hasher($data)
    {
        $key = $this->secret;
        if (strlen($key) > 64) {
            $key = pack('H40', sha1($key));
        }
        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }
        $ipad = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $opad = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
        return sha1($opad.pack('H40', sha1($ipad.$data)));
    }

    protected function base64($str)
    {
        $ret = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            $ret .= chr(hexdec(substr($str, $i, 2)));
        }
        return base64_encode($ret);
    }

    protected $mimeTypes = array(
        "323" => "text/h323",
        "acx" => "application/internet-property-stream",
        "ai" => "application/postscript",
        "aif" => "audio/x-aiff",
        "aifc" => "audio/x-aiff",
        "aiff" => "audio/x-aiff",
        "asf" => "video/x-ms-asf",
        "asr" => "video/x-ms-asf",
        "asx" => "video/x-ms-asf",
        "au" => "audio/basic",
        "avi" => "video/quicktime",
        "axs" => "application/olescript",
        "bas" => "text/plain",
        "bcpio" => "application/x-bcpio",
        "bin" => "application/octet-stream",
        "bmp" => "image/bmp",
        "c" => "text/plain",
        "cat" => "application/vnd.ms-pkiseccat",
        "cdf" => "application/x-cdf",
        "cer" => "application/x-x509-ca-cert",
        "class" => "application/octet-stream",
        "clp" => "application/x-msclip",
        "cmx" => "image/x-cmx",
        "cod" => "image/cis-cod",
        "cpio" => "application/x-cpio",
        "crd" => "application/x-mscardfile",
        "crl" => "application/pkix-crl",
        "crt" => "application/x-x509-ca-cert",
        "csh" => "application/x-csh",
        "css" => "text/css",
        "dcr" => "application/x-director",
        "der" => "application/x-x509-ca-cert",
        "dir" => "application/x-director",
        "dll" => "application/x-msdownload",
        "dms" => "application/octet-stream",
        "doc" => "application/msword",
        "dot" => "application/msword",
        "dvi" => "application/x-dvi",
        "dxr" => "application/x-director",
        "eps" => "application/postscript",
        "etx" => "text/x-setext",
        "evy" => "application/envoy",
        "exe" => "application/octet-stream",
        "fif" => "application/fractals",
        "flr" => "x-world/x-vrml",
        "gif" => "image/gif",
        "gtar" => "application/x-gtar",
        "gz" => "application/x-gzip",
        "h" => "text/plain",
        "hdf" => "application/x-hdf",
        "hlp" => "application/winhlp",
        "hqx" => "application/mac-binhex40",
        "hta" => "application/hta",
        "htc" => "text/x-component",
        "htm" => "text/html",
        "html" => "text/html",
        "htt" => "text/webviewhtml",
        "ico" => "image/x-icon",
        "ief" => "image/ief",
        "iii" => "application/x-iphone",
        "ins" => "application/x-internet-signup",
        "isp" => "application/x-internet-signup",
        "jfif" => "image/pipeg",
        "jpe" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "jpg" => "image/jpeg",
        "js" => "application/x-javascript",
        "latex" => "application/x-latex",
        "lha" => "application/octet-stream",
        "lsf" => "video/x-la-asf",
        "lsx" => "video/x-la-asf",
        "lzh" => "application/octet-stream",
        "m13" => "application/x-msmediaview",
        "m14" => "application/x-msmediaview",
        "m3u" => "audio/x-mpegurl",
        "man" => "application/x-troff-man",
        "mdb" => "application/x-msaccess",
        "me" => "application/x-troff-me",
        "mht" => "message/rfc822",
        "mhtml" => "message/rfc822",
        "mid" => "audio/mid",
        "mny" => "application/x-msmoney",
        "mov" => "video/quicktime",
        "movie" => "video/x-sgi-movie",
        "mp2" => "video/mpeg",
        "mp3" => "audio/mpeg",
        "mpa" => "video/mpeg",
        "mpe" => "video/mpeg",
        "mpeg" => "video/mpeg",
        "mpg" => "video/mpeg",
        "mpp" => "application/vnd.ms-project",
        "mpv2" => "video/mpeg",
        "ms" => "application/x-troff-ms",
        "mvb" => "application/x-msmediaview",
        "nws" => "message/rfc822",
        "oda" => "application/oda",
        "p10" => "application/pkcs10",
        "p12" => "application/x-pkcs12",
        "p7b" => "application/x-pkcs7-certificates",
        "p7c" => "application/x-pkcs7-mime",
        "p7m" => "application/x-pkcs7-mime",
        "p7r" => "application/x-pkcs7-certreqresp",
        "p7s" => "application/x-pkcs7-signature",
        "pbm" => "image/x-portable-bitmap",
        "pdf" => "application/pdf",
        "pfx" => "application/x-pkcs12",
        "pgm" => "image/x-portable-graymap",
        "pko" => "application/ynd.ms-pkipko",
        "pma" => "application/x-perfmon",
        "pmc" => "application/x-perfmon",
        "pml" => "application/x-perfmon",
        "pmr" => "application/x-perfmon",
        "pmw" => "application/x-perfmon",
        "png" => "image/png",
        "pnm" => "image/x-portable-anymap",
        "pot" => "application/vnd.ms-powerpoint",
        "ppm" => "image/x-portable-pixmap",
        "pps" => "application/vnd.ms-powerpoint",
        "ppt" => "application/vnd.ms-powerpoint",
        "prf" => "application/pics-rules",
        "ps" => "application/postscript",
        "pub" => "application/x-mspublisher",
        "qt" => "video/quicktime",
        "ra" => "audio/x-pn-realaudio",
        "ram" => "audio/x-pn-realaudio",
        "ras" => "image/x-cmu-raster",
        "rgb" => "image/x-rgb",
        "rmi" => "audio/mid",
        "roff" => "application/x-troff",
        "rtf" => "application/rtf",
        "rtx" => "text/richtext",
        "scd" => "application/x-msschedule",
        "sct" => "text/scriptlet",
        "setpay" => "application/set-payment-initiation",
        "setreg" => "application/set-registration-initiation",
        "sh" => "application/x-sh",
        "shar" => "application/x-shar",
        "sit" => "application/x-stuffit",
        "snd" => "audio/basic",
        "spc" => "application/x-pkcs7-certificates",
        "spl" => "application/futuresplash",
        "src" => "application/x-wais-source",
        "sst" => "application/vnd.ms-pkicertstore",
        "stl" => "application/vnd.ms-pkistl",
        "stm" => "text/html",
        "svg" => "image/svg+xml",
        "sv4cpio" => "application/x-sv4cpio",
        "sv4crc" => "application/x-sv4crc",
        "t" => "application/x-troff",
        "tar" => "application/x-tar",
        "tcl" => "application/x-tcl",
        "tex" => "application/x-tex",
        "texi" => "application/x-texinfo",
        "texinfo" => "application/x-texinfo",
        "tgz" => "application/x-compressed",
        "tif" => "image/tiff",
        "tiff" => "image/tiff",
        "tr" => "application/x-troff",
        "trm" => "application/x-msterminal",
        "tsv" => "text/tab-separated-values",
        "txt" => "text/plain",
        "uls" => "text/iuls",
        "ustar" => "application/x-ustar",
        "vcf" => "text/x-vcard",
        "vrml" => "x-world/x-vrml",
        "wav" => "audio/x-wav",
        "wcm" => "application/vnd.ms-works",
        "wdb" => "application/vnd.ms-works",
        "wks" => "application/vnd.ms-works",
        "wmf" => "application/x-msmetafile",
        "wps" => "application/vnd.ms-works",
        "wri" => "application/x-mswrite",
        "wrl" => "x-world/x-vrml",
        "wrz" => "x-world/x-vrml",
        "xaf" => "x-world/x-vrml",
        "xbm" => "image/x-xbitmap",
        "xla" => "application/vnd.ms-excel",
        "xlc" => "application/vnd.ms-excel",
        "xlm" => "application/vnd.ms-excel",
        "xls" => "application/vnd.ms-excel",
        "xlt" => "application/vnd.ms-excel",
        "xlw" => "application/vnd.ms-excel",
        "xof" => "x-world/x-vrml",
        "xpm" => "image/x-xpixmap",
        "xwd" => "image/x-xwindowdump",
        "z" => "application/x-compress",
        "zip" => "application/zip"
    );
}
