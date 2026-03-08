<!DOCTYPE html>
<html>
<head>
    <title>Centrifugo Test</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        #status { padding: 10px; margin: 10px 0; border-radius: 4px; font-weight: bold; }
        #messages { background: #f9f9f9; padding: 10px; border-radius: 4px; height: 300px; overflow-y: auto; font-family: monospace; }
        .message { padding: 5px; margin: 5px 0; border-bottom: 1px solid #eee; }
        .info { color: #2196F3; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
<div class="container">
    <h2>🧪 Centrifugo Connection Test</h2>

    <div style="margin-bottom: 20px;">
        <p><strong>Server:</strong> ws://91.219.60.148:8008/connection/websocket</p>
        <p><strong>Channel:</strong> teal-towel-48</p>
    </div>

    <div id="status">⏳ Initializing...</div>

    <div style="margin: 20px 0;">
        <button onclick="window.location.reload()">🔄 Перезагрузить</button>
        <button onclick="clearMessages()">🗑️ Очистить</button>
    </div>

    <div id="messages"></div>
</div>

<script src="https://unpkg.com/centrifuge@5.4.0/dist/centrifuge.js"></script>
<script type="text/javascript">
    const statusDiv = document.getElementById('status');
    const messagesDiv = document.getElementById('messages');

    // Ваш токен из конфига
    const TOKEN = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJ1c2VyMTIzIiwiaWF0IjoxNzcyOTc0MTkyfQ.9XdoylEOqbewEMuXcPgdlTU4sQ_XVohuEHt3WLkKkdk";
    const protocol = 'ws:';
    const URL = `${protocol}//91.219.60.148:8008/connection/websocket`;
    const CHANNEL = "teal-towel-48";

    function addMessage(msg, type = 'info') {
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.innerHTML = `<span style="color:#666;">${new Date().toLocaleTimeString()}</span> ${msg}`;
        messagesDiv.appendChild(div);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }

    function clearMessages() {
        messagesDiv.innerHTML = '';
        addMessage('🧹 Messages cleared', 'warning');
    }

    addMessage(`🚀 Connecting to ${URL}...`, 'info');

    const centrifuge = new Centrifuge(URL, {
        token: TOKEN
    });

    centrifuge.on('connecting', function (ctx) {
        statusDiv.innerHTML = '🔄 Connecting...';
        addMessage(`connecting: ${ctx.code}, ${ctx.reason}`, 'warning');
    }).on('connected', function (ctx) {
        statusDiv.innerHTML = '✅ Connected!';
        addMessage(`✅ connected over ${ctx.transport}`, 'success');

        // Подписываемся на канал
        addMessage(`📡 Subscribing to channel: ${CHANNEL}...`, 'info');

        const sub = centrifuge.newSubscription(CHANNEL);

        sub.on('publication', function (ctx) {
            addMessage(`📩 Received: ${JSON.stringify(ctx.data)}`, 'success');
            document.title = '📩 New message';
        }).on('subscribing', function (ctx) {
            addMessage(`subscribing: ${ctx.code}, ${ctx.reason}`, 'warning');
        }).on('subscribed', function (ctx) {
            addMessage(`✅ Subscribed to channel: ${CHANNEL}`, 'success');
        }).on('unsubscribed', function (ctx) {
            addMessage(`unsubscribed: ${ctx.code}, ${ctx.reason}`, 'error');
        }).subscribe();

    }).on('disconnected', function (ctx) {
        statusDiv.innerHTML = '❌ Disconnected';
        addMessage(`disconnected: ${ctx.code}, ${ctx.reason}`, 'error');
    }).on('error', function (ctx) {
        addMessage(`❌ Error: ${ctx.message}`, 'error');
    }).connect();
</script>
</body>
</html>
