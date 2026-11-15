document.documentElement.removeAttribute('data-theme');try{localStorage.removeItem('apa_theme')}catch(e){}document.querySelectorAll('[onclick="tg()"]').forEach(e=>e.remove());
document.addEventListener('DOMContentLoaded',()=>{
const io=new IntersectionObserver(e=>e.forEach(x=>{if(x.isIntersecting){x.target.classList.add('in');io.unobserve(x.target)}}),{threshold:.15});document.querySelectorAll('.rv').forEach(e=>io.observe(e));
const cnt=e=>{const to=+e.dataset.to,s=performance.now(),suf=e.dataset.suf||'';const f=t=>{const p=Math.min((t-s)/1400,1),v=Math.round(to*(1-Math.pow(1-p,3)));e.textContent=v.toLocaleString()+suf;if(p<1)requestAnimationFrame(f)};requestAnimationFrame(f)};
const co=new IntersectionObserver(e=>e.forEach(x=>{if(x.isIntersecting){cnt(x.target);co.unobserve(x.target)}}));document.querySelectorAll('[data-to]').forEach(e=>co.observe(e));
const t=document.querySelector('.top');if(t)addEventListener('scroll',()=>t.classList.toggle('sc',scrollY>10));
document.querySelectorAll('[data-fill]').forEach(b=>b.onclick=()=>{document.querySelector('[name=email]').value=b.dataset.fill;document.querySelector('[name=password]').value='password'});
document.querySelectorAll('.flash').forEach(f=>setTimeout(()=>{f.style.transition='opacity .6s';f.style.opacity=0},4500));
document.querySelectorAll('tr[data-mk]').forEach(r=>{const u=()=>{const v=[...r.querySelectorAll('input')].reduce((a,i)=>a+(+i.value||0),0);r.querySelector('.tot').textContent=v;r.querySelector('.gr').textContent=v>=80?'A':v>=70?'B':v>=60?'C':v>=50?'D':'E';r.querySelector('.cp').innerHTML=v>=70?'<span class="b ok">Competent</span>':'<span class="b bad">Not yet competent</span>'};r.querySelectorAll('input').forEach(i=>i.oninput=u)});
});
document.addEventListener('DOMContentLoaded',()=>{const S=[...document.querySelectorAll('.sl')];if(S.length){const D=document.querySelector('.dots'),photos=[...document.querySelectorAll('.hero-photo')];S.forEach((_,i)=>{const d=document.createElement('i');d.onclick=()=>go(i);D.appendChild(d)});let n=0,p=-1,t;const go=i=>{n=(i+S.length)%S.length;S.forEach((s,k)=>s.classList.toggle('on',k==n));if(photos.length){p=(p+1)%photos.length;photos.forEach((photo,k)=>photo.classList.toggle('is-visible',k===p))}const cu=document.getElementById('cur');if(cu)cu.textContent='0'+(n+1);[...D.children].forEach((d,k)=>{d.classList.remove('on');void d.offsetWidth;if(k==n)d.classList.add('on')});clearInterval(t);if(!matchMedia('(prefers-reduced-motion:reduce)').matches)t=setInterval(()=>go(n+1),6500)};document.getElementById('pv').onclick=()=>go(n-1);document.getElementById('nx').onclick=()=>go(n+1);go(0)}
document.querySelectorAll('.login-gallery').forEach(gallery=>{const photos=[...gallery.querySelectorAll('.login-photo')];if(photos.length<2||matchMedia('(prefers-reduced-motion:reduce)').matches)return;let current=0;setInterval(()=>{photos[current].classList.remove('is-visible');current=(current+1)%photos.length;photos[current].classList.add('is-visible')},6500)});
document.querySelectorAll('.tabs button').forEach(b=>b.onclick=()=>{document.querySelectorAll('.tabs button,.tp').forEach(x=>x.classList.remove('on'));b.classList.add('on');document.getElementById(b.dataset.t).classList.add('on')})});
document.addEventListener('DOMContentLoaded',()=>{const cn=()=>{const c={P:0,A:0,L:0,E:0};document.querySelectorAll('.seg input:checked').forEach(i=>c[i.value]++);for(const k in c){const e=document.querySelector('[data-c='+k+']');if(e)e.textContent=c[k]}};document.querySelectorAll('.seg input').forEach(i=>i.addEventListener('change',cn))});

document.addEventListener('DOMContentLoaded',()=>{
const list=document.querySelector('[data-guardian-list]'),add=document.querySelector('[data-add-guardian]');
if(!list||!add)return;
let index=list.children.length;
const refresh=()=>{add.disabled=list.children.length>=5};
add.addEventListener('click',()=>{
if(list.children.length>=5)return;
const row=document.createElement('div');row.className='toolbar guardian-row';row.setAttribute('data-guardian-row','');
row.innerHTML=`<div><label>Guardian name</label><input name="guardians[${index}][name]" required></div><div><label>Relationship</label><input name="guardians[${index}][relation]" placeholder="Mother, Father, Guardian"></div><div><label>Phone</label><input name="guardians[${index}][phone]" required></div><button type="button" data-remove-guardian>Remove</button>`;
index++;list.append(row);refresh();
});
list.addEventListener('click',event=>{
if(!(event.target instanceof Element)||!event.target.closest('[data-remove-guardian]'))return;
event.target.closest('[data-guardian-row]').remove();refresh();
});
refresh();
});


document.addEventListener('DOMContentLoaded',()=>{const h=document.querySelector('.h2o');if(!h||matchMedia('(prefers-reduced-motion:reduce)').matches)return;h.addEventListener('mousemove',e=>{const r=h.getBoundingClientRect();h.style.setProperty('--mx',((e.clientX-r.left)/r.width-.5).toFixed(3));h.style.setProperty('--my',((e.clientY-r.top)/r.height-.5).toFixed(3))})});
document.addEventListener('DOMContentLoaded',()=>{
const counters=[...document.querySelectorAll('[data-live-stat]')];
if(!counters.length)return;
setInterval(async()=>{
try{
const response=await fetch('/public/statistics',{headers:{Accept:'application/json'},cache:'no-store'});
if(!response.ok)throw new Error(`Statistics request failed with status ${response.status}`);
const totals=await response.json();
counters.forEach(counter=>{
const total=totals[counter.dataset.liveStat];
if(Number.isInteger(total)&&total>=0){counter.dataset.to=String(total);counter.textContent=total.toLocaleString()}
});
}catch(error){
console.error('Could not refresh the public school totals.',error);
}
},15000);
});
document.addEventListener('DOMContentLoaded',()=>{
document.querySelectorAll('[data-photo-rotation]').forEach(gallery=>{
const photos=[...gallery.querySelectorAll('.tvet-photo')];
if(photos.length<2||matchMedia('(prefers-reduced-motion:reduce)').matches)return;
let current=0;
setInterval(()=>{photos[current].classList.remove('is-visible');current=(current+1)%photos.length;photos[current].classList.add('is-visible')},5000);
});
const form=document.querySelector('[data-contact-form]');
if(!form)return;
const status=form.querySelector('.contact-status');
const button=form.querySelector('button[type="submit"],button:not([type])');
const showStatus=(message,success)=>{status.textContent=message;status.hidden=false;status.classList.toggle('contact-success',success);status.classList.toggle('contact-error',!success)};
form.addEventListener('submit',async event=>{
event.preventDefault();
if(!form.reportValidity())return;
button.disabled=true;
button.textContent='Sending...';
try{
const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
const result=await response.json();
if(response.ok){showStatus(result.message||'Thank you. Your message has been sent to the school.',true);form.reset();return}
showStatus(response.status===422?'Please enter your name, a valid email address, and a message before sending.':result.message||'Your message could not be sent right now. Please try again later or call the school.',false);
}catch(error){
showStatus('We could not connect to send your message. Please check your connection and try again.',false);
}finally{
button.disabled=false;
button.textContent='Send message';
}
});
});
function lowd(){var d=document.documentElement;if(d.dataset.low==='1'){delete d.dataset.low;try{localStorage.removeItem('apa_low')}catch(e){}}else{d.dataset.low='1';try{localStorage.setItem('apa_low','1')}catch(e){}}}
