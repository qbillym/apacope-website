(()=>{const cv=document.getElementById('globe');if(!cv)return;const g=cv.getContext('2d'),W=cv.width,R=W*.44,C=W/2,D=Math.PI/180,gs=document.getElementById('gs');
const LAND=[[[-17,21],[-16,13],[-9,5],[-1,5],[8,4],[9,-1],[13,-12],[12,-18],[15,-27],[19,-35],[26,-34],[33,-26],[36,-19],[40,-11],[40,-3],[51,12],[43,12],[36,22],[33,31],[20,32],[10,37],[-2,35],[-10,30]],
[[-10,36],[-9,43],[-2,48],[2,51],[8,54],[10,58],[22,60],[30,70],[40,68],[40,55],[30,45],[28,41],[24,36],[12,38],[8,44],[0,40]],
[[40,68],[80,74],[140,72],[170,66],[140,55],[130,40],[122,30],[110,20],[100,5],[104,10],[92,22],[80,10],[72,20],[58,24],[50,30],[36,36],[30,45],[40,55]],
[[36,30],[48,30],[58,24],[52,16],[44,12],[38,20]],
[[-168,66],[-140,70],[-95,72],[-60,60],[-55,50],[-70,44],[-80,30],[-97,26],[-105,20],[-90,15],[-80,8],[-100,18],[-118,32],[-125,48],[-135,58],[-160,58]],
[[-80,8],[-60,10],[-50,0],[-35,-6],[-40,-22],[-58,-38],[-66,-55],[-74,-50],[-72,-30],[-80,-5]],
[[114,-22],[130,-12],[142,-11],[153,-26],[146,-39],[132,-33],[115,-34]],[[44,-25],[50,-15],[48,-13],[43,-20]]];
const inside=(p,x,y)=>{let c=false;for(let i=0,j=p.length-1;i<p.length;j=i++){const a=p[i],b=p[j];if((a[1]>y)!=(b[1]>y)&&x<(b[0]-a[0])*(y-a[1])/(b[1]-a[1])+a[0])c=!c}return c};
const dots=[];for(let la=-58;la<=82;la+=3)for(let lo=-180;lo<180;lo+=3/Math.max(.3,Math.cos(la*D)))if(LAND.some(p=>inside(p,lo,la)))dots.push([lo,la]);
const P=(lo,la,l0,p0)=>{const a=(lo-l0)*D,b=la*D,q=p0*D;return[R*Math.cos(b)*Math.sin(a),R*(Math.cos(q)*Math.sin(b)-Math.sin(q)*Math.cos(b)*Math.cos(a)),Math.sin(q)*Math.sin(b)+Math.cos(q)*Math.cos(b)*Math.cos(a)]};
const RW=[29.87,-1.94];let l0=-100,p0=18,mark=0,t0=null;
function line(f,a,b,s){g.beginPath();let on=0;for(let v=a;v<=b;v+=4){const p=f(v);if(p[2]>0){g[on?'lineTo':'moveTo'](C+p[0],C-p[1]);on=1}else on=0}g.stroke()}
function draw(now){g.clearRect(0,0,W,W);const gr=g.createRadialGradient(C-R*.3,C-R*.35,R*.1,C,C,R);gr.addColorStop(0,'#2d5fb0');gr.addColorStop(1,'#0b1d40');g.fillStyle=gr;g.beginPath();g.arc(C,C,R,0,7);g.fill();
g.strokeStyle='#ffffff20';g.lineWidth=1;for(let la=-60;la<=60;la+=30)line(lo=>P(lo-180,la,l0,p0),0,360);for(let lo=-180;lo<180;lo+=30)line(la=>P(lo,la-80,l0,p0),0,160);
for(const d of dots){const p=P(d[0],d[1],l0,p0);if(p[2]>0){g.fillStyle='rgba(255,255,255,'+(.2+.75*p[2]/R)+')';g.fillRect(C+p[0]-1.3,C-p[1]-1.3,2.6,2.6)}}
g.strokeStyle='#8db8ff66';g.lineWidth=2;g.beginPath();g.arc(C,C,R,0,7);g.stroke();
if(mark){const p=P(RW[0],RW[1],l0,p0);if(p[2]>0){const k=(now/700)%1;g.strokeStyle='rgba(227,27,35,'+(1-k)+')';g.lineWidth=2.5;g.beginPath();g.arc(C+p[0],C-p[1],7+k*30,0,7);g.stroke();g.fillStyle='#e31b23';g.beginPath();g.arc(C+p[0],C-p[1],7,0,7);g.fill();g.strokeStyle='#fff';g.lineWidth=2;g.beginPath();g.arc(C+p[0],C-p[1],7,0,7);g.stroke()}}}
const ease=x=>x<.5?2*x*x:1-Math.pow(-2*x+2,2)/2,from=-100+2.8*38;
function tick(now){if(t0===null)t0=now;const t=(now-t0)/1000;
if(t<2.8){l0=-100+t*38;p0=18}else if(t<5.4){const k=ease((t-2.8)/2.6);l0=from+(RW[0]-from)*k;p0=18+(RW[1]-18)*k}else{l0=RW[0];p0=RW[1]}
if(t>4.7)mark=1;draw(now);if(t<7.2)requestAnimationFrame(tick)}
const RWP=[[28.86,-2.35],[29.02,-1.69],[29.58,-1.39],[29.82,-1.30],[30.02,-1.05],[30.47,-1.07],[30.82,-1.48],[30.90,-2.05],[30.80,-2.35],[30.55,-2.40],[30.42,-2.78],[30.05,-2.74],[29.64,-2.92],[29.26,-2.80],[29.02,-2.72],[28.90,-2.62]];
const k=173.9,X=lo=>(lo-28.75)*k,Y=la=>(-0.95-la)*k,pts=RWP.map(p=>X(p[0]).toFixed(1)+','+Y(p[1]).toFixed(1)).join(' '),kx=X(30.06),ky=Y(-1.95);
let h='<defs><linearGradient id="rg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#3b78d0"/><stop offset="1" stop-color="#14284b"/></linearGradient></defs>';
for(let i=3;i>=1;i--)h+='<polygon points="'+pts+'" transform="translate(200 180) scale('+(1+i*.06)+') translate(-200 -180)" fill="none" stroke="#fff" stroke-opacity="'+(.07*i)+'"/>';
h+='<polygon points="'+pts+'" fill="url(#rg)" stroke="#9cc3ff" stroke-width="2.5" stroke-linejoin="round"/>';
for(let i=1;i<=3;i++)h+='<polygon points="'+pts+'" transform="translate(200 180) scale('+(1-i*.2)+') translate(-200 -180)" fill="none" stroke="#fff" stroke-opacity=".16"/>';
h+='<text x="200" y="352" fill="#c9d6ee" font-size="15" text-anchor="middle" font-family="Georgia,serif">Rwanda</text><g transform="translate('+kx+' '+ky+')"><g class="pn"><circle class="pulse" r="12" fill="#e31b23" opacity=".35"/><circle r="7" fill="#e31b23" stroke="#fff" stroke-width="2.5"/></g></g>';
document.getElementById('rw').innerHTML=h;
const delay=document.getElementById('curtain')?2900:400,T=(f,ms)=>setTimeout(f,delay+ms);
if(matchMedia('(prefers-reduced-motion:reduce)').matches){l0=RW[0];p0=RW[1];mark=1;draw(0);gs.classList.add('zoom','drop','show');return}
draw(0);T(()=>requestAnimationFrame(tick),0);T(()=>gs.classList.add('zoom'),6100);T(()=>gs.classList.add('drop'),7700);T(()=>gs.classList.add('show'),8700);
})();
