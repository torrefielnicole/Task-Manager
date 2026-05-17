<?php include 'sidebar.php'; ?>

<div class="page-content">
  <h1 class="dev-title">Developer</h1>
  <p class="dev-sub">// api endpoints · v2.4.1</p>

  <div class="api-list">
    <div class="api-row">
      <span class="method m-get">GET</span>
      <span class="api-path">/api/tasks</span>
      <span class="api-status">200 OK</span>
    </div>
    <div class="api-row">
      <span class="method m-post">POST</span>
      <span class="api-path">/api/tasks/new</span>
      <span class="api-status">201</span>
    </div>
    <div class="api-row">
      <span class="method m-put">PUT</span>
      <span class="api-path">/api/tasks/:id</span>
      <span class="api-status">200 OK</span>
    </div>
    <div class="api-row">
      <span class="method m-del">DEL</span>
      <span class="api-path">/api/tasks/:id</span>
      <span class="api-status">204</span>
    </div>
    <div class="api-row">
      <span class="method m-get">GET</span>
      <span class="api-path">/api/users/me</span>
      <span class="api-status">200 OK</span>
    </div>
  </div>

  <div class="code-snip">
    <span style="color:#1D9E75;">fetch</span>(<span style="color:#BA7517;">'/api/tasks'</span>, {<br>
    &nbsp;&nbsp;method: <span style="color:#BA7517;">'GET'</span>,<br>
    &nbsp;&nbsp;headers: { <span style="color:#BA7517;">'Authorization'</span>: <span style="color:#BA7517;">`Bearer ${token}`</span> }<br>
    }).<span style="color:#1D9E75;">then</span>(r =&gt; r.<span style="color:#1D9E75;">json</span>())<br>
    &nbsp;&nbsp;.<span style="color:#1D9E75;">then</span>(data =&gt; <span style="color:#1D9E75;">console</span>.<span style="color:#378ADD;">log</span>(data));
  </div>
</div>

<?php include 'footer.php'; ?>