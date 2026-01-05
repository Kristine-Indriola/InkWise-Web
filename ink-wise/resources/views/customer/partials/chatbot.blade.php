<!-- Chat bot AI assistance widget -->
<div class="chat-widget" x-data="chatBot" x-init="loadQAs()" @keydown.window.escape="open=false">
  <!-- Toggle button -->
  <div class="chat-btn" @click="open = !open; if(open) loadQAs()" aria-label="Open chat">
    <div class="chat-inner"><i class="bi bi-question-circle-fill text-3xl text-cyan-500"></i></div>
  </div>

  <!-- Chat panel -->
  <div x-show="open" x-cloak x-transition class="chat-panel" @click.away="open = false">
    <div class="chat-header">
      <div class="chat-avatar"><i class="bi bi-question-circle-fill text-2xl text-cyan-500"></i></div>
      <div><h4>InkWise Assistant</h4><div class="chat-subtitle">FAQ help for templates & orders</div></div>
    </div>

    <!-- Fixed Question List -->
    <div class="faq-list p-3 border-b bg-gray-50" x-show="qas.length > 0">
      <p class="faq-title mb-2">Select a question:</p>
      <div class="flex flex-col gap-2">
        <template x-for="qa in qas" :key="qa.id">
          <button type="button" class="faq-btn text-left px-3 py-2 rounded border shadow-sm transition"
                  @click="askQuestion(qa)">
            <span x-text="qa.question"></span>
          </button>
        </template>
      </div>
    </div>

    <!-- Chat body (messages are direct children here) -->
    <div class="chat-body" x-ref="body">
      <template x-for="(m, idx) in messages" :key="idx">
        <!-- single element per message; class bound by sender -->
        <div :class="m.from === 'user' ? 'msg user' : 'msg bot'">
          <template x-if="m.from !== 'user'">
            <div class="avatar"><i class="bi bi-question-circle-fill text-xl text-cyan-500"></i></div>
          </template>
          <div class="bubble">
            <div class="text" x-text="m.text" x-show="m.text"></div>
            <template x-if="m.image">
              <img :src="m.image" alt="Answer image" class="bubble-image" loading="lazy">
            </template>
            <div class="time" x-text="m.time ? m.time : ''"></div>
          </div>
        </div>
      </template>
    </div>

    <div class="chat-input-note px-4 py-3 text-sm text-gray-600 bg-gray-50 border-t">Select a question above to view the answer.</div>
  </div>
</div>

<style>
  /* FAQ Styles */
  .faq-title { font-size: .95rem; font-weight: 600; color: #374151; }
  .faq-btn { background:#f9fafb; border:1px solid #e5e7eb; font-size:.9rem; cursor:pointer; }
  .faq-btn:hover { background:#2563eb; color:#fff; border-color:#2563eb; }

  .chat-subtitle { font-size: 0.85rem; color: #6b7280; font-weight: 400; }

  .chat-body {
    padding:16px;
    max-height:400px;
    overflow-y:auto;
    display:flex;
    flex-direction:column;
    gap:14px;
    scroll-behavior:smooth;
    background: linear-gradient(180deg, rgba(6,182,212,0.02), transparent);
  }

  .msg { display:inline-flex; position:relative; max-width:78%; padding:12px 14px; border-radius:16px; font-size:15px; line-height:1.4; word-break:break-word; box-shadow:0 4px 12px rgba(4,29,66,0.08); align-items:flex-start; gap:10px; }
  .msg .avatar { width:32px; height:32px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .msg .bubble { display:flex; flex-direction:column; gap:6px; }

  .msg.bot { background: linear-gradient(180deg,#f4f8ff,#eaf3ff); align-self:flex-start; color:#03305a; border-bottom-left-radius:6px; }
  .msg.user { background: linear-gradient(180deg,#e6f7fb,#c9f0f5); align-self:flex-end; color:#022a37; border-bottom-right-radius:6px; justify-content:flex-end; }

  .msg.bot::after, .msg.user::after { content:""; position:absolute; top:16px; width:14px; height:14px; transform:rotate(45deg); box-shadow:0 8px 14px rgba(4,29,66,0.03); border-radius:2px; z-index:0; background:inherit; }
  .msg.bot::after { left:-7px; } .msg.user::after { right:-7px; }

  /* ensure user bubble text aligns right */
  .msg.user .bubble { text-align: right; }
</style>

<script>
  document.addEventListener("alpine:init", () => {
    Alpine.data("chatBot", () => ({
      open: false,
      messages: [
        { from: 'bot', text: "Hi! I'm InkWise Assistant. Choose a question below to see the answer.", time: '' }
      ],
      qas: [],

      async loadQAs() {
        try {
          const res = await fetch("{{ route('chatbot.qas') }}", { headers: { 'Accept': 'application/json' }});
          if (!res.ok) throw new Error('Network error');
          const payload = await res.json();
          this.qas = Array.isArray(payload) ? payload.map(qa => ({
            id: qa.id,
            question: qa.question,
            answer: qa.answer,
            image: qa.answer_image_url || null,
          })) : [];
        } catch (err) {
          console.error("Failed to load QAs:", err);
        }
      },

      askQuestion(qa) {
        this.messages.push({ from: "user", text: qa.question, time: new Date().toLocaleTimeString() });
        this.scrollToBottom();
        setTimeout(() => {
          this.messages.push({ from: "bot", text: qa.answer, image: qa.image, time: new Date().toLocaleTimeString() });
          this.scrollToBottom();
        }, 150);
      },

      scrollToBottom() {
        this.$nextTick(() => { this.$refs.body.scrollTop = this.$refs.body.scrollHeight; });
      }
    }))
  })
</script>
