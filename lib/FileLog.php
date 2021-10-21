<?
class FileLog {
	/**
     * @var string
     */
	private $file;

	/**
     * @var array
     */
	private $jsonData;

	/**
     * @var array
     */
	private $uniqueUrls;

	/**
     * @var int
     */
	private $currentLineFile;

	/**
     * @var array
     */
	private $errors;

	public function __construct(string $pathFile = 'access_log') {
		$this->file  = fopen($pathFile, "r");
		$this->errors = [];

		if(!$this->file){
			throw new Exception("Не удалось открыть файл {$pathFile}");
		}

		$this->jsonData = [
			'views'			=> 0,
			'urls' 			=> 0,
			'traffic'		=> 0,
			'crawlers'		=> [
			    'Google'		=> 0,
			    'Bing'			=> 0,
			    'Baidu'			=> 0,
			    'Yandex'		=> 0
			],
			'statusCodes'	=> []
		];
	}

	public function readLogFile(): void {
		$this->uniqueUrls		 = [];
		$this->currentLineFile	 = 0;

		while(!feof($this->file)) {
			$this->currentLineFile ++;
	        $this->readLineLogFile();
	    }

	    $this->jsonData['urls'] = count($this->uniqueUrls);
	}

	private function readLineLogFile(): void {
	    $lineLog = trim(fgets($this->file));

    	$lineLogData = $this->parsingLineLogFile($lineLog);
    	
    	if(count($lineLogData)>0){
	    	$this->jsonData['views'] ++;
	    	$this->jsonData['traffic'] += $lineLogData['traffic'];

	    	if(array_key_exists($lineLogData['crawler'], $this->jsonData['crawlers'])){
	    		$this->jsonData['crawlers'][$lineLogData['crawler']] ++;
	    	}

	    	if(array_key_exists($lineLogData['statusCode'], $this->jsonData['statusCodes'])){
	    		$this->jsonData['statusCodes'][$lineLogData['statusCode']] ++;
	    	}else{
	    		$this->jsonData['statusCodes'][$lineLogData['statusCode']] = 1;
	    	}
	    }
	}

	/**
	 * @return array
	 */
	private function parsingLineLogFile(string $lineLog): array {
		$result		 = [];
		$lineLogData = [];

	    preg_match("/(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")/", $lineLog, $lineLogData);

	    if(count($lineLogData) != 14){
	    	$this->setError('Не известный формат строки');
	    	return $result;
	    }

	    $lineLogData = [
	    	'path'	 => $lineLogData[8],
	    	'status' => $lineLogData[10],
	    	'bytes'	 => $lineLogData[11],
	    	'agent'	 => $lineLogData[13],
	    ];

	    $this->addUrl($lineLogData['path']);

	    $result['traffic'] = 0;
	    if($lineLogData['status'] == '200'){
	    	$result['traffic'] = $lineLogData['bytes'];
	    }

	    $result['statusCode'] = $lineLogData['status'];

	    if(strstr($lineLogData['agent'], ' Googlebot/')){
	    	$result['crawler'] = 'Google';
	    }elseif(strstr($lineLogData['agent'], ' Bingbot/')){
	    	$result['crawler'] = 'Bing';
	    }elseif(strstr($lineLogData['agent'], ' Baidubot/')){
	    	$result['crawler'] = 'Baidu';
	    }elseif(strstr($lineLogData['agent'], ' Yandexbot/')){
	    	$result['crawler'] = 'Yandex';
	    }

	    return $result;
	}

	private function addUrl(string $url): void {
		if(array_key_exists($url, $this->uniqueUrls)){
	    	$this->uniqueUrls[$url] ++;
	    }else{
	    	$this->uniqueUrls[$url] = 1;
	    }
	}

	private function setError(string $errorMessage): void {
	    $this->errors[] = [
	    	'line'		 => $this->currentLineFile,
	    	'message'	 => $errorMessage
	    ];
	}

	/**
	 * @return array
	 */
	public function errors(): array {
	    return $this->errors;
	}

	/**
	 * @return string
	 */
	public function jsonData(): string {
		return json_encode($this->jsonData, JSON_PRETTY_PRINT);
	}

	public function __destruct() {
		fclose($this->file);
	}
}