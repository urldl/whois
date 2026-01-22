<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>批量域名/IP Whois 查询</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
body {
    font-family: "Microsoft YaHei", sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 20px;
}

/* 主容器 */
.container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto 80px auto; /* 给 footer 留空间 */
    background: #fff;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* 标题 */
h2 {
    text-align: center;
    color: #333;
    margin-bottom: 25px;
}

/* 文本域 */
.position-relative {
    position: relative;
    margin-bottom: 15px;
}

textarea {
    width: 100%;
    height: 160px;
    padding: 12px;
    font-size: 14px;
    border-radius: 6px;
    border: 1px solid #ccc;
    resize: none;
    box-sizing: border-box;
}

/* 清空按钮 */
#clearInputBtn {
    position: absolute;
    right: 12px;
    bottom: 12px;
    padding: 4px 10px;
    font-size: 12px;
    border: none;
    border-radius: 4px;
    background: #f44336;
    color: #fff;
    cursor: pointer;
    display: none;
}

/* 所有按钮统一一行一个 */
button {
    display: block;
    width: 100%;
    margin-top: 12px;
    padding: 12px;
    font-size: 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button.query-btn {
    background: #4CAF50;
    color: #fff;
}

button:disabled {
    background: #999;
    cursor: not-allowed;
}

/* 状态信息 */
.stats,
#currentDomain,
#taskStats {
    margin-top: 10px;
    font-size: 14px;
    color: #555;
}

/* 结果区域 */
#results {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
    border-top: 1px solid #eee;
    padding-top: 10px;
}

.result-item {
    padding: 10px 0;
    border-bottom: 1px dashed #ddd;
    white-space: pre-wrap;
    line-height: 1.6;
}

.result-success {
    color: #2e7d32;
}

.result-error {
    color: #d32f2f;
}

/* 打包按钮（一行一个） */
.pack-btn {
    background: #2196F3;
    color: #fff;
    display: none;
}

.pack-fail-btn {
    background: #f44336;
    color: #fff;
    display: none;
}

/* 底部 footer */
.footer {
    width: 100%;
    padding: 8px 15px;
    background: #f0f2f5;
    text-align: center;
    font-size: 14px;
    color: #555;
    position: fixed;
    bottom: 0;
    left: 0;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
}

/* footer 按钮一行一个（手机更友好） */
.footer-btn {
    display: inline-block;
    margin: 4px 6px;
    padding: 4px 12px;
    font-size: 13px;
    color: #fff;
    background-color: #4CAF50;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
}

.footer-btn:hover {
    background-color: #45a049;
}
</head>
<body>

<div class="container">
    <h2>批量域名/IP Whois 查询</h2>
    <div class="position-relative">
        <textarea id="domains" placeholder="每行输入一个域名或IP，例如：example.com 或 https://example.com，支持二级或完整链接输入自动识别主域名，单次最多支持1000个"></textarea>
        <button id="clearInputBtn">清空</button>
    </div>
    <div class="stats" id="domainCount">已输入 0 个域名</div>
    <button id="queryBtn" class="query-btn">查询</button>
    <button id="packBtn" class="pack-btn">打包成功结果</button>
    <button id="packFailBtn" class="pack-fail-btn">打包失败域名</button>

    <div id="currentDomain"></div>
    <div id="taskStats"></div>

    <h3>查询结果：</h3>
    <div id="results"></div>
</div>

<script>
const textarea = document.getElementById('domains');
const domainCount = document.getElementById('domainCount');
const queryBtn = document.getElementById('queryBtn');
const resultsDiv = document.getElementById('results');
const currentDomainDiv = document.getElementById('currentDomain');
const taskStatsDiv = document.getElementById('taskStats');
const packBtn = document.getElementById('packBtn');
const packFailBtn = document.getElementById('packFailBtn');
const clearInputBtn = document.getElementById('clearInputBtn');

let currentResults = []; 
let failedDomains = [];

// 自动统计域名数量，显示清空按钮
textarea.addEventListener('input', () => {
    // 保留换行，只去掉每行首尾空格
    let lines = textarea.value.split('\n').map(d => d.trim()).filter(d => d.length > 0);

    // 限制最大1000个域名
    if(lines.length > 1000){
        lines = lines.slice(0, 1000);
        textarea.value = lines.join('\n');
        alert('最多支持1000个域名，已截断超出部分');
    }

    domainCount.textContent = `已输入 ${lines.length} 个域名`;
    packBtn.style.display = 'none';
    packFailBtn.style.display = 'none';
    currentDomainDiv.textContent = '';
    taskStatsDiv.textContent = '';
    currentResults = [];
    failedDomains = [];
    clearInputBtn.style.display = lines.length > 0 ? 'block' : 'none';
});

// 点击输入框清空输出信息，保留输入内容
textarea.addEventListener('focus', () => {
    resultsDiv.innerHTML = '';
    currentDomainDiv.textContent = '';
    taskStatsDiv.textContent = '';
    packBtn.style.display = 'none';
    packFailBtn.style.display = 'none';
    currentResults = [];
    failedDomains = [];
});

// 清空输入按钮
clearInputBtn.addEventListener('click', () => {
    textarea.value = '';
    domainCount.textContent = `已输入 0 个域名`;
    resultsDiv.innerHTML = '';
    currentDomainDiv.textContent = '';
    taskStatsDiv.textContent = '';
    packBtn.style.display = 'none';
    packFailBtn.style.display = 'none';
    clearInputBtn.style.display = 'none';
    currentResults = [];
    failedDomains = [];
});

// 显示主域名
function displayDomain(domain){
    try{
        let url = new URL(domain);
        return url.hostname;
    } catch(e){
        return domain.split(/[\/:]/)[0];
    }
}

// 查询函数
queryBtn.addEventListener('click', async () => {
    let domains = textarea.value.split('\n').map(d=>d.trim()).filter(d=>d.length>0);
    if(domains.length===0){ alert('请输入域名'); return; }

    resultsDiv.innerHTML = '';
    currentResults = [];
    failedDomains = [];
    currentDomainDiv.textContent = '';
    taskStatsDiv.textContent = '';

    queryBtn.disabled = true;
    queryBtn.textContent = '查询中，请稍等...';
    packBtn.style.display = 'none';
    packFailBtn.style.display = 'none';

    let successCount=0, failCount=0;
    const showFullOutput = domains.length <= 50; // 是否输出每条结果

    for(let i=0;i<domains.length;i++){
        const fullDomain = domains[i];
        const displayName = displayDomain(fullDomain);
        currentDomainDiv.textContent = `正在查询域名：${displayName}`;

        try{
            const res = await fetch('batch_whois.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body: JSON.stringify({domains:[fullDomain]})
            });
            const data = await res.json();
            const item = data[0];
            currentResults.push(item);

            // 输出逻辑
            if(showFullOutput){
                const div = document.createElement('div');
                div.className = 'result-item ' + (item.status==='success'?'result-success':'result-error');
                div.innerHTML = item.status==='success' 
                    ? `<strong>查询域名：${item.domain}</strong>\n<pre>${item.whois}</pre>` 
                    : `<strong>查询域名：${item.domain}</strong> 错误: ${item.message}`;
                resultsDiv.appendChild(div);

                // 自动滚动到最新域名的分隔线
                div.scrollIntoView({behavior:'smooth', block:'start'});
            } else {
                resultsDiv.textContent = `已查询 ${i+1} 个域名...完整结果请等待任务结束并打包获取`;
            }

            if(item.status==='success') successCount++;
            else { failCount++; failedDomains.push(item.domain); }

        }catch(e){
            failCount++;
            failedDomains.push(fullDomain);
            currentResults.push({domain:fullDomain,status:'error',message:e.message});

            if(showFullOutput){
                const div = document.createElement('div');
                div.className = 'result-item result-error';
                div.innerHTML = `<strong>查询域名：${fullDomain}</strong> 错误: ${e.message}`;
                resultsDiv.appendChild(div);
                div.scrollIntoView({behavior:'smooth', block:'start'});
            } else {
                resultsDiv.textContent = `已查询 ${i+1} 个域名...完整结果请等待任务结束并打包获取`;
            }
        }

        // 更新任务数量和状态
        let statsText = `任务数量：${domains.length} 已完成：${successCount + failCount} 成功：${successCount}`;
        if(failCount>0) statsText += ` 失败：${failCount}`;
        taskStatsDiv.textContent = statsText;
    }

    // 任务完成
    currentDomainDiv.textContent = '查询完成！';
    queryBtn.disabled = false;
    queryBtn.textContent = '查询';

    // 打包按钮逻辑，至少2个域名才显示
    if(domains.length >= 2){
        if(successCount>0) packBtn.style.display = 'inline-block';
        if(failCount>0) packFailBtn.style.display = 'inline-block';
    }
});

// 打包函数
function packResults(results, filename){
    if(results.length===0) return;
    const now = new Date();
    const timestamp = now.getFullYear()+'-'+(now.getMonth()+1).toString().padStart(2,'0')+'-'+now.getDate().toString().padStart(2,'0')+'_'+now.getHours().toString().padStart(2,'0')+now.getMinutes().toString().padStart(2,'0')+now.getSeconds().toString().padStart(2,'0');

    let content = `批量查询结果 - ${timestamp}\n\n`;
    results.forEach(item=>{
        content += '====================\n';
        content += `查询域名：${item.domain}\n`;
        if(item.status==='success') content += item.whois+'\n';
        else content += '错误: '+item.message+'\n';
        content += '====================\n\n';
    });

    const blob = new Blob([content], {type:'text/plain'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename ? `${filename}_${timestamp}.txt` : `whois_${timestamp}.txt`;
    link.click();
}

// 打包按钮事件
packBtn.addEventListener('click', ()=> {
    const successResults = currentResults.filter(r=>r.status==='success');
    packResults(successResults,'whois_success');
});

packFailBtn.addEventListener('click', ()=> {
    const failList = currentResults.filter(r=>r.status==='error');
    packResults(failList,'whois_fail');
});
</script>
<!-- 底部版权信息 -->
<div class="footer">
    <span>© <span id="year"></span> - <a href="http://itusu.cn" target="_blank" class="footer-btn">ITUSN.CN</a> - All rights reserved.</span>
</div>
<script>
// 自动获取当前年份
document.getElementById('year').textContent = new Date().getFullYear();
</script>
</body>
</html>
