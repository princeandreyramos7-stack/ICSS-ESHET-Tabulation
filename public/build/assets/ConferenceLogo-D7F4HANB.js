import{r as o,j as i}from"./app-L8KW5w0q.js";/**
 * @license lucide-react v0.483.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const p=t=>t.replace(/([a-z0-9])([A-Z])/g,"$1-$2").toLowerCase(),c=(...t)=>t.filter((e,r,n)=>!!e&&e.trim()!==""&&n.indexOf(e)===r).join(" ").trim();/**
 * @license lucide-react v0.483.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */var w={xmlns:"http://www.w3.org/2000/svg",width:24,height:24,viewBox:"0 0 24 24",fill:"none",stroke:"currentColor",strokeWidth:2,strokeLinecap:"round",strokeLinejoin:"round"};/**
 * @license lucide-react v0.483.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const C=o.forwardRef(({color:t="currentColor",size:e=24,strokeWidth:r=2,absoluteStrokeWidth:n,className:s="",children:a,iconNode:u,...g},m)=>o.createElement("svg",{ref:m,...w,width:e,height:e,stroke:t,strokeWidth:n?Number(r)*24/Number(e):r,className:c("lucide",s),...g},[...u.map(([f,d])=>o.createElement(f,d)),...Array.isArray(a)?a:[a]]));/**
 * @license lucide-react v0.483.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const h=(t,e)=>{const r=o.forwardRef(({className:n,...s},a)=>o.createElement(C,{ref:a,iconNode:e,className:c(`lucide-${p(t)}`,n),...s}));return r.displayName=`${t}`,r},l={conference:{src:"/img/ICSSESHET.jpg",alt:"2nd International Conference on Sustainable Solutions"},university:{src:"/img/Isu_logo.jpg",alt:"Isabela State University"},city:{src:"/img/Ilagan.png",alt:"City of Ilagan, Isabela"},campusPhoto:{src:"/img/ISUilagan_Gate.png",alt:"Isabela State University, City of Ilagan Campus gate"}};function I({className:t="size-12",ring:e=!0}){return i.jsx("span",{className:`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-white ${e?"ring-2 ring-amber-400/80":""} ${t}`,children:i.jsx("img",{src:l.conference.src,alt:l.conference.alt,className:"h-full w-full object-cover"})})}export{I as C,l as L,h as c};
