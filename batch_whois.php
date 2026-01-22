<?php
header('Content-Type: application/json');

// 获取前端 JSON 数据
$input = json_decode(file_get_contents('php://input'), true);
$domains = $input['domains'] ?? [];

if(empty($domains)){
    echo json_encode([]);
    exit;
}

$url = "https://itusu.cn/";
$results = [];
$logDir = __DIR__.'/logs';
if(!is_dir($logDir)) mkdir($logDir, 0777, true);

$today = date('Y-m-d');
$logFile = "$logDir/query_$today.json";
$existingLogs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

function parseWhois($whois){
    $result = [
        'registrant'=>'',
        'email'=>'',
        'registrar'=>'',
        'create_date'=>'',
        'expire_date'=>''
    ];

    // 国内域名
    if(preg_match('/Registrant:\s*(.+)/i', $whois, $m)) $result['registrant'] = trim($m[1]);
    if(preg_match('/Registrant Contact Email:\s*(.+)/i', $whois, $m)) $result['email'] = trim($m[1]);
    if(preg_match('/Sponsoring Registrar:\s*(.+)/i', $whois, $m)) $result['registrar'] = trim($m[1]);
    if(preg_match('/Registration Time:\s*(.+)/i', $whois, $m)) $result['create_date'] = trim($m[1]);
    if(preg_match('/Expiration Time:\s*(.+)/i', $whois, $m)) $result['expire_date'] = trim($m[1]);

    // 国际域名
    if(preg_match('/Registrant:\s*(.+)/i', $whois, $m)) $result['registrant'] = $result['registrant'] ?: trim($m[1]);
    if(preg_match('/Registrar Abuse Contact Email:\s*(.+)/i', $whois, $m)) $result['email'] = $result['email'] ?: trim($m[1]);
    if(preg_match('/Registrar:\s*(.+)/i', $whois, $m)) $result['registrar'] = $result['registrar'] ?: trim($m[1]);
    if(preg_match('/Creation Date:\s*(.+)/i', $whois, $m)) $result['create_date'] = $result['create_date'] ?: trim($m[1]);
    if(preg_match('/Registry Expiry Date:\s*(.+)/i', $whois, $m)) $result['expire_date'] = $result['expire_date'] ?: trim($m[1]);

    return $result;
}

foreach($domains as $domain){
    $data = http_build_query(['domain'=>$domain]);
    $options = [
        'http'=>[
            'header'=>"Content-type: application/x-www-form-urlencoded\r\n",
            'method'=>'POST',
            'content'=>$data,
            'timeout'=>10
        ]
    ];
    $context = stream_context_create($options);
    $queryTime = date('Y-m-d H:i:s');

    try {
        $resultStr = @file_get_contents($url,false,$context);
        $json = @json_decode($resultStr,true);

        if($json && $json['status']==='success'){
            $parsed = parseWhois($json['whois']);
            $item = [
                'domain'=>$json['domain'],
                'whois'=>$json['whois'],
                'status'=>'success',
                'query_time'=>$queryTime,
                'registrant'=>$parsed['registrant'],
                'email'=>$parsed['email'],
                'registrar'=>$parsed['registrar'],
                'create_date'=>$parsed['create_date'],
                'expire_date'=>$parsed['expire_date']
            ];
        } else {
            $item = [
                'domain'=>$domain,
                'message'=>$json['message'] ?? '查询失败',
                'status'=>'error',
                'query_time'=>$queryTime
            ];
        }
    } catch(Exception $e){
        $item = [
            'domain'=>$domain,
            'message'=>$e->getMessage(),
            'status'=>'error',
            'query_time'=>$queryTime
        ];
    }

    $results[] = $item;
    $existingLogs[] = $item;
}

// 保存日志
file_put_contents($logFile, json_encode($existingLogs, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
echo json_encode($results, JSON_UNESCAPED_UNICODE);
