<style>
  /* Templates section scoped styles */
  #templates { padding-top: 3rem; padding-bottom: 3rem; }
  .section-divider { width: 64px; height: 3px; background: linear-gradient(90deg,#06b6d4,#0891b2); margin: 0 auto 1.5rem; border: 0; }
  .gradient-pink { background: linear-gradient(135deg,#fff6fb 0%, #fff0f6 50%, #fffaf9 100%); }
  .gradient-yellow { background: linear-gradient(135deg,#fffaf0 0%, #fff6e0 50%, #fffdf9 100%); }
  .gradient-orange { background: linear-gradient(135deg,#fff8f2 0%, #fff4ee 50%, #fffdf9 100%); }
  .gradient-skyblue { background: linear-gradient(135deg,#f5fbff 0%, #f0faff 50%, #fbfdff 100%); }

  #templates .relative { width: min(1100px, 95%); }
  #templates .rounded-3xl { border-radius: 1rem; }
  #templates a.block { display: block; }

  /* Card visuals */
  #templates .shadow-xl { box-shadow: 0 10px 30px rgba(2,6,23,0.06); }
  #templates .border-4 { border-width: 4px; }
  #templates h3 { font-family: 'Seasons', serif; margin-top: 0.5rem; }
  #templates p { margin-top: 0.25rem; }

  /* Responsive - stack cards on small screens */
  @media (max-width: 860px) {
    #templates .flex { flex-direction: column; gap: 1rem; }
    #templates .flex-row-reverse { flex-direction: column-reverse; }
    #templates a.block { width: 100%; transform: none !important; }
    #templates img.absolute { display: none; }
  }

  /* Interactive hover improvements */
  #templates a.block:hover { transform: translateY(-6px) scale(1.03); box-shadow: 0 18px 40px rgba(2,6,23,0.08); }

</style>

<section id="templates" class="py-12 relative overflow-hidden">
  <!-- animated themed background canvas (wedding, baptism, birthday) -->
  <canvas id="templatesCanvas" class="absolute inset-0 w-full h-full pointer-events-none" aria-hidden="true"></canvas>

  <div class="layout-container relative z-10">
    <hr class="section-divider">
    <h2 class="text-center text-3xl font-bold text-indigo-700 mb-10" style="font-family: 'Seasons', serif;">
      Find your perfect match
    </h2>

    <div class="flex flex-col gap-12 items-center">

    <!-- Birthday (Image left) -->
    <div class="relative flex items-center gap-8 gradient-pink p-6 rounded-3xl">
      <img src="{{ asset('Customerimages/image/Star.png') }}" class="absolute -top-6 -left-8 w-12 h-12 rotate-12" alt="">
      <img src="{{ asset('Customerimages/image/Gift.png') }}" class="absolute -bottom-6 right-10 w-12 h-12" alt="">

      
      <a href="{{ route('templates.birthday.invitations') }}" class="block w-80 -rotate-3 rounded-3xl shadow-xl overflow-hidden border-4 border-pink-300 cursor-pointer hover:scale-105 transition-transform">
        <img src="{{ asset('Customerimages/image/birthday.png') }}" alt="Birthday" class="w-full h-40 object-cover">
      </a>

      <div class="max-w-sm">
        <h3 class="text-pink-600 text-2xl text-center font-extrabold">Birthday</h3>
        <p class="text-pink-500 text-sm text-center">
          Choose from Unique Birthday Invitation and Giveaways Designs
        </p>
      </div>
    </div>

    <!-- Wedding (Image right) -->
    <div class="relative flex items-center gap-8 gradient-yellow p-6 rounded-3xl flex-row-reverse">
      <img src="{{ asset('Customerimages/image/Ring.png') }}" class="absolute -top-6 -right-8 w-20 h-20 rotate-12 z-20" alt="">
      <img src="{{ asset('Customerimages/image/Ribbon.png') }}" class="absolute -bottom-6 left-10 w-12 h-12 rotate-12" alt="">

      <a href="{{ route('templates.wedding.invitations') }}" class="block w-80 rotate-3 rounded-3xl shadow-xl overflow-hidden border-4 border-yellow-400 cursor-pointer hover:scale-105 transition-transform">
        <img src="{{ asset('Customerimages/image/wedding.png') }}" alt="Wedding" class="w-full h-40 object-cover">
      </a>

      <div class="max-w-sm">
        <h3 class="text-yellow-700 text-center text-2xl font-extrabold">Wedding</h3>
        <p class="text-yellow-600 text-sm text-center">
          Choose from Unique Wedding Invitation and Giveaways Designs
        </p>
      </div>
    </div>

    <!-- Corporate (Image left) -->
    <div class="relative flex items-center gap-8 gradient-orange p-6 rounded-3xl">
      <img src="{{ asset('Customerimages/image/Glass.png') }}" class="absolute -top-6 -left-8 w-20 h-20 rotate-12 z-20" alt="">
      <img src="{{ asset('Customerimages/image/Paperclip.png') }}" class="absolute -bottom-6 left-44 w-12 h-12 rotate-12 z-20" alt="">

      <a href="{{ route('templates.corporate.invitations') }}" class="block w-80 -rotate-2 rounded-3xl shadow-xl overflow-hidden border-4 border-orange-400 cursor-pointer hover:scale-105 transition-transform">
        <img src="{{ asset('Customerimages/image/corporate.png') }}" alt="Corporate" class="w-full h-40 object-cover">
      </a>

      <div class="max-w-sm">
        <h3 class="text-orange-600 text-center text-2xl font-extrabold">Corporate</h3>
        <p class="text-black-500 text-center">
          Choose from Unique Corporate Invitation and Giveaways Designs
        </p>
      </div>
    </div>

    <!-- Baptism (Image right) -->
    <div class="relative flex items-center gap-8 gradient-skyblue p-6 rounded-3xl flex-row-reverse">
      <img src="{{ asset('Customerimages/image/footprint.png') }}" class="absolute -top-6 -right-8 w-20 h-20 rotate-12 z-20" alt="">
      <img src="{{ asset('Customerimages/image/Cloud.png') }}" class="absolute -bottom-8 left-32 w-12 h-12 rotate-12" alt="">

      <a href="{{ route('templates.baptism.invitations') }}" class="block w-80 rotate-3 rounded-3xl shadow-xl overflow-hidden border-4 border-blue-400 cursor-pointer hover:scale-105 transition-transform">
        <img src="{{ asset('Customerimages/image/baptism.png') }}" alt="Baptism" class="w-full h-40 object-cover">
      </a>

      <div class="max-w-sm">
        <h3 class="text-blue-600 text-2xl font-extrabold text-center">Baptism</h3>
        <p class="text-orange-500 text-sm text-center">
          Choose from Unique Baptism Invitation and Giveaways Designs
        </p>
      </div>
    </div>

    </div>
  </div>
</section>
<script>
// Themed animated background for Templates (wedding, baptism, birthday)
(function () {
  const canvas = document.getElementById('templatesCanvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  let W = 0, H = 0, DPR = window.devicePixelRatio || 1;

  function resize() {
    DPR = window.devicePixelRatio || 1;
    W = canvas.clientWidth || window.innerWidth;
    H = canvas.clientHeight || 600;
    canvas.width = Math.round(W * DPR);
    canvas.height = Math.round(H * DPR);
    canvas.style.width = W + 'px';
    canvas.style.height = H + 'px';
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
  }

  // shape factories
  function makeConfetti(x,y,z){
    return {type:'confetti', x,y,z, w: (6 + Math.random()*10)*(1+z*0.6), h: (8+Math.random()*8)*(1+z*0.6), hue: 330+Math.random()*120, rot: Math.random()*Math.PI};
  }
  function makeBalloon(x,y,z){ return {type:'balloon', x,y,z, r: 10+Math.random()*18, hue: 170+Math.random()*40, float: Math.random()*0.3+0.1}; }
  function makeRing(x,y,z){ return {type:'ring', x,y,z, R: 12+Math.random()*14, line: 3+Math.random()*3, rot: Math.random()*Math.PI}; }
  function makeCake(x,y,z){ return {type:'cake', x,y,z, w: 24+Math.random()*16, h: 14+Math.random()*10, hue: 30+Math.random()*30}; }
  function makeHat(x,y,z){ return {type:'hat', x,y,z, w: 18+Math.random()*14, h: 12+Math.random()*10, hue: 10+Math.random()*50}; }
  function makeFoot(x,y,z){ return {type:'foot', x,y,z, s: 10+Math.random()*8}; }

  let items = [];
  function initItems(){
    items = [];
    for(let i=0;i<40;i++){
      const z = Math.random();
      const x = Math.random()*W; const y = Math.random()*H;
      const t = Math.random();
      if (t < 0.45) items.push(makeConfetti(x,y,z));
      else if (t < 0.65) items.push(makeBalloon(x,y,z));
      else if (t < 0.78) items.push(makeRing(x,y,z));
      else if (t < 0.88) items.push(makeCake(x,y,z));
      else if (t < 0.96) items.push(makeHat(x,y,z));
      else items.push(makeFoot(x,y,z));
    }
  }

  let mouse = {x: W/2, y: H/2};
  window.addEventListener('mousemove', function(e){
    const r = canvas.getBoundingClientRect(); mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top;
  }, {passive:true});

  function drawItem(p, t){
    const depth = 0.3 + p.z*0.9;
    const ox = (mouse.x - W/2) * (0.02 + p.z*0.06);
    const oy = (mouse.y - H/2) * (0.02 + p.z*0.03);
    const x = p.x + Math.sin(t*0.5 + p.z*10)*6 + ox;
    const y = p.y + Math.cos(t*0.3 + p.z*5)*8 + oy;

    ctx.save();
    if (p.type === 'confetti'){
      ctx.translate(x,y); ctx.rotate(p.rot + t*0.02*(1+p.z));
      ctx.fillStyle = `hsl(${p.hue},70%,60%)`;
      roundRect(ctx, -p.w/2, -p.h/2, p.w, p.h, 2); ctx.fill();
    } else if (p.type === 'balloon'){
      ctx.beginPath(); ctx.fillStyle = `hsl(${p.hue},70%,60%)`; ctx.ellipse(x,y, p.r*(1+0.15*p.z), p.r*1.15, 0, 0, Math.PI*2); ctx.fill();
      ctx.strokeStyle = 'rgba(0,0,0,0.07)'; ctx.lineWidth = 0.8; ctx.stroke();
      // string
      ctx.beginPath(); ctx.moveTo(x, y + p.r*1.15); ctx.lineTo(x + 1*p.z, y + p.r*1.15 + 18); ctx.strokeStyle='rgba(0,0,0,0.12)'; ctx.stroke();
    } else if (p.type === 'ring'){
      ctx.beginPath(); ctx.strokeStyle = `hsl(45,60%,60%)`; ctx.lineWidth = p.line*(1+p.z*0.4); ctx.ellipse(x, y, p.R*(1+p.z*0.1), p.R*(1+p.z*0.08), p.rot + t*0.002, 0, Math.PI*2); ctx.stroke();
      // small glint
      ctx.fillStyle = 'rgba(255,255,255,0.7)'; ctx.fillRect(x+p.R*0.6, y-p.R*0.05, 2, 2);
    } else if (p.type === 'cake'){
      ctx.fillStyle = `hsl(${p.hue},70%,62%)`; roundRect(ctx, x - p.w/2, y - p.h, p.w, p.h, 3); ctx.fill();
      ctx.fillStyle = '#fff'; roundRect(ctx, x - p.w/3, y - p.h + 4, p.w/1.5, p.h/3, 2); ctx.fill();
    } else if (p.type === 'hat'){
      ctx.fillStyle = `hsl(${p.hue},70%,50%)`; ctx.beginPath(); ctx.moveTo(x, y - p.h/2); ctx.lineTo(x + p.w/2, y + p.h/2); ctx.lineTo(x - p.w/2, y + p.h/2); ctx.closePath(); ctx.fill();
    } else if (p.type === 'foot'){
      ctx.fillStyle = '#d3e9ff'; roundRect(ctx, x-p.s/2, y-p.s/2, p.s, p.s*0.6, p.s*0.3); ctx.fill();
    }
    ctx.restore();
  }

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath(); ctx.moveTo(x+r,y); ctx.arcTo(x+w,y,x+w,y+h,r); ctx.arcTo(x+w,y+h,x,y+h,r); ctx.arcTo(x,y+h,x,y,r); ctx.arcTo(x,y,x+w,y,r); ctx.closePath();
  }

  let running = true;
  let last = 0;
  function animate(ts){
    if (!running) return;
    const t = ts*0.001;
    ctx.clearRect(0,0,W,H);
    // soft background wash
    const g = ctx.createLinearGradient(0,0,0,H); g.addColorStop(0,'rgba(250,250,255,0.4)'); g.addColorStop(1,'rgba(255,255,255,0.8)'); ctx.fillStyle = g; ctx.fillRect(0,0,W,H);
    items.forEach((p,i)=>{ drawItem(p,t); p.y += 0.2 + p.z*0.6; if (p.y - 60 > H) p.y = -40 - Math.random()*50; });
    requestAnimationFrame(animate);
  }

  // pause when out of view
  const obs = new IntersectionObserver(entries=>{
    entries.forEach(e=>{ running = e.isIntersecting; if (running) requestAnimationFrame(animate); });
  }, {threshold: 0.1});
  obs.observe(canvas);

  window.addEventListener('resize', function(){ resize(); initItems(); });
  resize(); initItems(); requestAnimationFrame(animate);
})();
</script>
