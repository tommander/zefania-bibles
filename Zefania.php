<?php
/**
 * Zefania class unit
 */

/**
 * Converts Zefania XML files to JSON files that are specifically used by the Order of Mass web app
 */
class Zefania
{
    /**
     * List of ignored files
     * 
     * @var string[]
     */
    private $ignoredFiles = [
        ".",
        "..",
        "SF_2006-03-02_ENG_TTB_(TORREY'S TOPICAL TEXTBOOK).xml", //NaB (Not a Bible)
        "SF_2009-01-20_GER_SCH1951_(SCHLACHTER BIBEL 1951 WITH STRONG).xml", //Same as the non-strong version
        "SF_2009-01-20_GRC_BZY2000_(BYZANTINE MAJORITY TEXT (2000 PLUS STRONGS)).xml", //Same as the non-strong version
        "SF_2009-01-20_GRC_TISCHENDORF_(TISCHENDORF GREEK NT(STRONGS)).xml", //Same as the non-strong version
        "SF_2009-01-22_ENG_RWEBSTER_(REVISED 1833 WEBSTER VERSION WITH STRONGS).xml", //Same as the non-strong version
        "SF_2009-01-20_ENG_KJV_(KJV+).xml", //Same as the non-strong version
        "SF_2009-01-20_GER_PAT80_(PATTLOCH BIBEL).xml", //Newer version exists
        "SF_2010-06-10_GER_GERNEUE_(NEUE EVANGELISTISCHE ÜBERSETZUNG).xml", //Newer version exists
        "SF_2012-01-07_GER_GERNEUE_(NEUE EVANGELISTISCHE ÜBERSETZUNG).xml", //Newer version exists
        "SF_2014-02-25_GER_GERNEUE_(NEUE EVANGELISTISCHE ÜBERSETZUNG).xml", //Newer version exists
        "SF_2009-01-20_ENG_BWE_(BIBLE IN WORLDWIDE ENGLISH NT).xml", //Newer version exists
        "SF_2012-02-07_ENG_NHEB_(NEW HEART ENGLISH BIBLE).xml", //Newer version exists
        "SF_2009-01-20_CZE_CZBKR_(CZECH BKR).xml", //Newer version exists
        "SF_2009-07-31_CZE_CZEB21_(CZECH BIBLE, PREKLAD 21. STOLETI).xml", //Newer version exists
        "SF_2009-01-23_CZE_CZECEP_(CZECH EKUMENICKY CESKY PREKLAD).xml", //Newer version exists
        "SF_2016-01-01_GER_OFFBILE_(OFFENE BIBEL - LESEFASSUNG).xml", //Newer version exists
        "SF_2016-01-01_GER_OFFBIST_(OFFENE BIBEL - STUDIENFASSUNG).xml", //Newer version exists
        "SF_2014-02-26_ENG_CPDV_(CATHOLIC PUBLIC DOMAIN VERSION).xml", //Newer version exists
        "SF_2009-01-20_GER_LUT_1545_LH_(LUTHER 1545 (LETZTE HAND)).xml", //Newer version exists
        "SF_2009-01-20_GER_NEÜ_(NEUE EVANGELISTISCHE ÜBERSETZUNG).xml", //Newer version exists
        "SF_2014-02-26_ENG_NHEBJE_(NEW HEART ENGLISH BIBLE - JEHOVAH EDITION).xml", //Newer version exists
        "SF_2014-02-26_ENG_NHEBME_(NEW HEART ENGLISH BIBLE - MESSIANIC EDITION).xml", //Newer version exists
        "SF_2014-02-25_GRC_BYZ_(THE NEW TESTAMENT IN THE ORIGINAL GREEK - BYZANTINE TEXTFORM 2005).xml", //Newer version exists
    ];
    /**
     * Output JSON content for a single XML
     *
     * @var array
     */
    private $outJson = [];
    /**
     * Output Meta content for a single XML
     *
     * @var array
     */
    private $outMeta = ["md5" => "", "file" => "", "meta" => []];
    /**
     * List of all Meta information for XML files
     *
     * @var array
     */
    private $outMetaAll = [];
    /**
     * Current book number
     * 
     * @var int
     */
    private $bookNum = 0;
    /**
     * Current chapter number
     * 
     * @var int
     */
    private $chapterNum = 0;
    /**
     * Current verse number
     * 
     * @var int
     */
    private $verseNum = 0;
    /**
     * Base directory
     * 
     * @var string
     */
    private $theDir = '';
    /**
     * List of XML files
     * 
     * @var string[]
     */
    private $fileList = [];
    /**
     * Verse flag. True when a <verse> start tag was found until the end tag.
     */
    private $flag = false;
    /**
     * Information flag. Same as verse flag, but for <information> tag. It also has higher priority.
     */
    private $infoflag = false;
    /**
     * Current child name of <information> node.
     */
    private $infoTag = '';
    /**
     * Temporary collector of verse text
     */
    private $tempVal = '';
    /**
     * Stack of booleans. Each boolean is an indicator for a subnode of <verse>, whether its text will be preserved or ignored.
     */
    private $stack = [];
    /**
     * List of unknown subnodes of <verse> node (i.e. they are neither whitelisted nor blacklisted). Having at least one item here stops the execution of the script.
     */
    private $unknowns = [];
    /**
     * List of all languages encountered in XML files
     */
    private $langList = [];
    /**
     * Map of book numbers to names (for each XML file)
     */
    private $bookMap = [];
    /**
     * List of common abbreviations of the project Order of Mass
     */
    private $commonAbbr = [];

    /**
     * Output a text with ISO-formatted date
     */
    private function textout(string $text)
    {
        printf("[%s] %s\r\n", date('c'), $text);
    }

    /**
     * Like `\implode()`, but for an associative array.
     */
    private function implode($array) {
        $ret = '';
        $b = true;
        foreach ($array as $k=>$v) {
            if (!$b) {
                $ret .= ',';
            } else {
                $b = false;
            }
            $ret .= sprintf('"%s"="%s"', $k, $v);
        }
        return $ret;
    }

    /**
     * Check if XML element is blacklisted (i.e. its inner text will not be collected incl. start/end tag and subnodes)
     */
    private function isBlacklisted(string $name, array $attribs)
    {
        if ($name === 'NOTE' && count($attribs) === 1 && isset($attribs['TYPE']) && ($attribs['TYPE'] === 'x-erased' || $attribs['TYPE'] === 'x-emphasize' || $attribs['TYPE'] === 'study')) {
            return true;
        }

        if ($name === 'STYLE' || $name === 'B' || $name === 'BR' || $name === 'I' || $name === 'STRONG' || $name === 'EM') {
            return true;
        }

        if ($name === 'XREF' && (count($attribs) === 0 || (count($attribs) === 1 && isset($attribs['MSCOPE'])))) {
            return true;
        }

        return false;
    }

    /**
     * Check if XML element is whitelisted (i.e. its inner text will be collected, but not the start/end tag)
     */
    private function isWhitelisted(string $name, array $attribs)
    {
        if ($name === 'NOTE' && count($attribs) === 1 && isset($attribs['TYPE']) && $attribs['TYPE'] === 'x-studynote') {
            return true;
        }
        if ($name === 'DIV' && count($attribs) === 0) {
            return true;
        }
        if ($name === 'GR' && count($attribs) >= 1 && count($attribs) <= 2 && (isset($attribs['STR']) || isset($attribs['RMAC']))) {
            return true;
        }

        if ($name === 'GRAM' && count($attribs) === 1 && isset($attribs['STR'])) {
            return true;
        }

        if ($name === 'XREF' && count($attribs) === 1 && isset($attribs['FSCOPE'])) {
            return true;
        }


        return false;
    }

    /**
     * Handler for start tag (for xml_parser)
     */
    private function handlerS($parser, string $name, array $attribs)
    {
        //Information
        if ($name === 'INFORMATION') {
            $this->infoflag = true;
            return;
        }

        if ($this->infoflag) {
            $this->infoTag = $name;
            $this->outMeta['meta'][$this->infoTag] = '';
            return;
        }

        //Book
        if ($name === 'BIBLEBOOK') {

            if (isset($attribs['BNUMBER']) !== true || $attribs['BNUMBER'] === '') {
                throw new \Exception('Bible Book not numbered!');
            }

            $this->bookNum = intval($attribs['BNUMBER']);
            if ($this->bookNum <= 0) {
                throw new \Exception('Bible Book invalid number "'.$attribs['BNUMBER'].'"!');
            }

            $bnames = [
                'full' => '',
                'short' => '',
            ];
            if (isset($attribs['BNAME']) && $attribs['BNAME'] !== '') {
                $bnames['full'] = $attribs['BNAME'];
            }
            if (isset($attribs['BSNAME']) && $attribs['BSNAME'] !== '') {
                $bnames['short'] = $attribs['BSNAME'];
            }

            $this->bookMap[$this->bookNum] = $bnames;

            return;
        }

        if ($name === 'CHAPTER') {
            if (isset($attribs['CNUMBER']) !== true) {
                throw new \Exception('Chapter number missing!');
            }
            $this->chapterNum = intval($attribs['CNUMBER']);
            return;
        }

        //Starting verse, brace yourselves
        if ($name === 'VERS') {
            if ($attribs['VNUMBER'] !== "0") {
                if (isset($attribs['VNUMBER']) !== true) {
                    throw new \Exception('Chapter number missing!');
                }
                    $this->verseNum = intval($attribs['VNUMBER']);
                $this->flag = true;
            }
            $this->tempVal = '';
            $this->stack = [true];
            return;
        }

        //If we are out of verse, do not care
        if (!$this->flag) {
            return;
        }

        //No need to check blacklist if we already do not include text
        if ($this->stack[count($this->stack)-1] !== true) {
            $this->stack[] = false;
            return;
        }

        //Check blacklist
        if ($this->isBlacklisted($name, $attribs)) {
            $this->stack[] = false;
            return;
        }

        //Check whitelist
        if (!$this->isWhitelisted($name, $attribs)) {
            $tagid = hash('sha256', $name.$this->implode($attribs));
            if (in_array($tagid, $this->unknowns) !== true) {
                $this->unknowns[] = $tagid;
                $this->textout(sprintf('Unknown inner tag "%s" with attributes "%s"', $name, $this->implode($attribs)));
            }
        }

        //Not blacklisted, let's collect the text
        $this->stack[] = true;
    }

    /**
     * Return a common Bible verse reference
     */
    private static function refVer(int $book, int $chap, int $ver, int $sta=0): int
    {
        return (int) sprintf('%d%03d%03d%01d', $book, $chap, $ver, $sta);

    }//end refVer()

    /**
     * Take a common Bible verse reference and return its parts
     */
    private static function decodeRefVer(int $refVer): array
    {
        $ret = [
            'book' => 0,
            'chap' => 0,
            'vers' => 0,
            'part' => 0,
        ];
        $strRefVer = strval($refVer);
        if (preg_match('/^(\d+)(\d{3})(\d{4})(\d{2})$/', $strRefVer, $mat) === 1) {
            $ret['book'] = $mat[1];
            $ret['chap'] = $mat[2];
            $ret['vers'] = $mat[3];
            $ret['part'] = $mat[4];
        }
        return $ret;

    }//end refVer()

    /**
     * Handler for end tag (for xml_parser)
     */
    private function handlerE($parser, string $name)
    {
        //Information
        if ($name === 'INFORMATION') {
            $this->infoflag = false;
            return;
        }

        if ($this->infoflag) {
            $this->infoTag = '';
            return;
        }

        if ($name === 'BIBLEBOOK') {
            $this->bookNum = 0;
            return;
        }

        if ($name === 'CHAPTER') {
            $this->chapterNum = 0;
            return;
        }

        if ($name === 'VERS') {
            $this->tempVal = preg_replace(['/\(\d+\)/', '/\[\d+\]/'], '', $this->tempVal);
            $this->tempVal = trim($this->tempVal);
            if ($this->tempVal !== '') {
                if ($this->bookNum <= 0) {
                    throw new \Exception('Book number is zero/negative!');
                }
                if ($this->chapterNum <= 0) {
                    throw new \Exception('Chapter number is zero/negative!');
                }
                if ($this->verseNum <= 0) {
                    throw new \Exception('Verse number is zero/negative!');
                }
                $this->outJson[self::refVer($this->bookNum,$this->chapterNum, $this->verseNum)] = $this->tempVal;
            }
            $this->flag = false;
            $this->stack = [];
            return;
        }

        if (!$this->flag) {
            return;
        }

        //Decrease current subnode depth
        array_pop($this->stack);
    }

    /**
     * Default handler for xml_parser
     */
    private function handlerM($parser, string $data)
    {
        if ($this->infoflag && $this->infoTag !== '') {
            $this->outMeta['meta'][$this->infoTag] .= $data;
            return;
        }

        if (!$this->flag) {
            return;
        }

        if ($this->stack[count($this->stack)-1] !== true) {
            return;
        }

        $this->tempVal .= $data;
    }

    /**
     * Process one Zefania XML file
     */
    private function processFile(string $fileName)
    {
        if (in_array($fileName, $this->ignoredFiles)) {
            $this->textout('Ignored');
            return true;
        }

        $theFile = $this->theDir . 'xml' . DIRECTORY_SEPARATOR . $fileName;
        $theFileJson = $this->theDir . 'json' . DIRECTORY_SEPARATOR . $fileName . '.json';
        $theFileMeta = $this->theDir . 'meta' . DIRECTORY_SEPARATOR . $fileName . '.json';
        $theFileMap = $this->theDir . 'map' . DIRECTORY_SEPARATOR . $fileName . '.json';

        $this->unknowns = [];
        $this->outJson = [];
        $this->outMeta = ["md5" => md5_file($theFile), "file" => $fileName . '.json', "meta" => []];
        $this->bookMap = [];

        $stream = fopen($theFile, 'r');
        $parser = xml_parser_create();
        xml_set_element_handler($parser, [$this, 'handlerS'], [$this, 'handlerE']);
        xml_set_default_handler($parser, [$this, 'handlerM']);
        while (feof($stream) !== true) {
            $data = fread($stream, 16384);
            xml_parse($parser, $data);
        }
        xml_parse($parser, '', true);
        xml_parser_free($parser);
        fclose($stream);
        unset($data);

        file_put_contents($theFileJson, json_encode($this->outJson));
        file_put_contents($theFileMeta, json_encode($this->outMeta, JSON_PRETTY_PRINT));
        file_put_contents($theFileMap, json_encode($this->bookMap, JSON_PRETTY_PRINT));
        $outMetaAllLang = 'unknown';
        $outMetaAllId = 'unknown';
        if (isset($this->outMeta['meta']['LANGUAGE']) && isset($this->outMeta['meta']['IDENTIFIER'])) {
            $outMetaAllLang = trim(strtolower($this->outMeta['meta']['LANGUAGE']));
            $outMetaAllId = trim(strtolower($this->outMeta['meta']['IDENTIFIER']));
        } else {
            throw new \Exception('Language or identifier unset');
        }
        if (isset($this->outMetaAll[$outMetaAllLang]) !== true) {
            $this->outMetaAll[$outMetaAllLang] = [];
        }
        if (isset($this->outMetaAll[$outMetaAllLang][$outMetaAllId])) {
            throw new \Exception('Identifier not unique');
        }
        if (!in_array($outMetaAllLang, $this->langList)) {
            $this->langList[] = $outMetaAllLang;
        }

        $this->outMetaAll[$outMetaAllLang][$outMetaAllId] = $this->outMeta;

        return true;
    }

    /** 
     * Constructor
     */
    public function __construct()
    {
        $this->theDir = __DIR__ . DIRECTORY_SEPARATOR;
        $this->fileList = scandir($this->theDir . 'xml');
        $this->fileList = is_array($this->fileList) ? $this->fileList : [];

        $commonAbbrFile = file_get_contents($this->theDir . '../../assets/json/booklist.json');
        $commonAbbrFile = is_string($commonAbbrFile) ? $commonAbbrFile : '';
        $this->commonAbbr = json_decode($commonAbbrFile, true);
        $this->commonAbbr = is_array($this->commonAbbr) ? $this->commonAbbr : [];
        $this->commonAbbr = array_keys($this->commonAbbr);
    }

    /**
     * Start processing XML files
     */
    public function run()
    {
        $fileIndex = 0;
        foreach($this->fileList as $file) {
            if (str_starts_with($file, 'SF_') !== true) {
                continue;
            }
            if (str_ends_with($file, '.xml') !== true) {
                continue;
            }

            $fileIndex++;
            $this->textout("Processing file #${fileIndex}: \"${file}\"", true);
            $this->processFile($file);
            if (count($this->unknowns) > 0) {
                break;
            }
        }

        $theFileIndex = $this->theDir . 'index.json';
        $theFileIndexMin = $this->theDir . 'index.min.json';
        file_put_contents($theFileIndex, json_encode($this->outMetaAll, JSON_PRETTY_PRINT));
        file_put_contents($theFileIndexMin, json_encode($this->outMetaAll));

        $iso6393 = file_get_contents('iso639-3_list.json');
        $iso6393 = is_string($iso6393) ? $iso6393 : '';
        $iso6393 = json_decode($iso6393, true);
        $iso6393k = array_keys($iso6393);
        $iso6393k = array_map(static function ($n) {
            return strtolower($n);
        }, $iso6393k);
        
        foreach($this->langList as $lang) {
            if (!in_array($lang, $iso6393k)) {
                printf('Warning: language "%s" missing in ISO 639-3', $lang);
            }
        }
    }
}

$zef = new Zefania();
$zef->run();

?>