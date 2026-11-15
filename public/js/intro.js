(()=>{const b=document.body,add=(...c)=>c.forEach(x=>b.classList.add(x)),fast=sessionStorage.getItem('apa')||matchMedia('(prefers-reduced-motion:reduce)').matches;
function count(){document.querySelectorAll('[data-to]').forEach(e=>{const to=+e.dataset.to;let s=null;const f=t=>{s??=t;const p=Math.min((t-s)/1400,1);e.textContent=Math.round(to*p).toLocaleString();if(p<1)requestAnimationFrame(f)};requestAnimationFrame(f)})}
function end(){add('k2','k3','k4','k5','k6');count();sessionStorage.setItem('apa',1);document.querySelector('.stage').style.display='none'}
if(fast||document.querySelector('.err')){end();return}
const T=[[1300,'k2'],[2300,'k3'],[5200,'k4'],[7400,'k5'],[8800,'k6']];T.forEach(([t,c])=>setTimeout(()=>{add(c);if(c=='k5')count();if(c=='k4')document.querySelector('.stage').style.opacity=0;if(c=='k6')sessionStorage.setItem('apa',1)},t));
document.getElementById('skip').onclick=end;})();
