import{G as a,B as u,aa as g,N as p,o as i,l as c,c as y,y as s,a4 as f,m as d,x as m,g as b,t as v}from"./app-BV9eXnr8.js";async function E(n,t=null){const e=t?{exclude:t}:{},{data:o}=await a.get(`/admin/vehicles/check-vin/${encodeURIComponent(n)}`,{params:e});return o.data}async function M(n){const{data:t}=await a.get(`/admin/vehicles/decode-vin/${encodeURIComponent(n)}`);return t.data}async function D(n){const{data:t}=await a.post("/admin/vehicles",n);return t}async function I(n,t){const{data:e}=await a.put(`/admin/vehicles/${n}`,t);return e}async function h(n){const{data:t}=await a.delete(`/admin/vehicles/${n}`);return t}const O=h;async function R(n){const{data:t}=await a.post(`/admin/vehicles/${n}/restore`);return t}async function U(){const{data:n}=await a.get("/admin/settings/vehicle-options");return n.data}var k=`
    .p-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: dt('tag.primary.background');
        color: dt('tag.primary.color');
        font-size: dt('tag.font.size');
        font-weight: dt('tag.font.weight');
        padding: dt('tag.padding');
        border-radius: dt('tag.border.radius');
        gap: dt('tag.gap');
    }

    .p-tag-icon {
        font-size: dt('tag.icon.size');
        width: dt('tag.icon.size');
        height: dt('tag.icon.size');
    }

    .p-tag-rounded {
        border-radius: dt('tag.rounded.border.radius');
    }

    .p-tag-success {
        background: dt('tag.success.background');
        color: dt('tag.success.color');
    }

    .p-tag-info {
        background: dt('tag.info.background');
        color: dt('tag.info.color');
    }

    .p-tag-warn {
        background: dt('tag.warn.background');
        color: dt('tag.warn.color');
    }

    .p-tag-danger {
        background: dt('tag.danger.background');
        color: dt('tag.danger.color');
    }

    .p-tag-secondary {
        background: dt('tag.secondary.background');
        color: dt('tag.secondary.color');
    }

    .p-tag-contrast {
        background: dt('tag.contrast.background');
        color: dt('tag.contrast.color');
    }
`,w={root:function(t){var e=t.props;return["p-tag p-component",{"p-tag-info":e.severity==="info","p-tag-success":e.severity==="success","p-tag-warn":e.severity==="warn","p-tag-danger":e.severity==="danger","p-tag-secondary":e.severity==="secondary","p-tag-contrast":e.severity==="contrast","p-tag-rounded":e.rounded}]},icon:"p-tag-icon",label:"p-tag-label"},$=u.extend({name:"tag",style:k,classes:w}),S={name:"BaseTag",extends:g,props:{value:null,severity:null,rounded:Boolean,icon:String},style:$,provide:function(){return{$pcTag:this,$parentInstance:this}}};function r(n){"@babel/helpers - typeof";return r=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},r(n)}function P(n,t,e){return(t=V(t))in n?Object.defineProperty(n,t,{value:e,enumerable:!0,configurable:!0,writable:!0}):n[t]=e,n}function V(n){var t=B(n,"string");return r(t)=="symbol"?t:t+""}function B(n,t){if(r(n)!="object"||!n)return n;var e=n[Symbol.toPrimitive];if(e!==void 0){var o=e.call(n,t);if(r(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(n)}var z={name:"Tag",extends:S,inheritAttrs:!1,computed:{dataP:function(){return p(P({rounded:this.rounded},this.severity,this.severity))}}},T=["data-p"];function j(n,t,e,o,C,l){return i(),c("span",s({class:n.cx("root"),"data-p":l.dataP},n.ptmi("root")),[n.$slots.icon?(i(),y(f(n.$slots.icon),s({key:0,class:n.cx("icon")},n.ptm("icon")),null,16,["class"])):n.icon?(i(),c("span",s({key:1,class:[n.cx("icon"),n.icon]},n.ptm("icon")),null,16)):d("",!0),n.value!=null||n.$slots.default?m(n.$slots,"default",{key:2},function(){return[b("span",s({class:n.cx("label")},n.ptm("label")),v(n.value),17)]}):d("",!0)],16,T)}z.render=j;export{D as a,O as b,E as c,M as d,h as e,U as f,R as r,z as s,I as u};
