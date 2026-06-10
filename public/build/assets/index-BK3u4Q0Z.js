import{B as P,I as z,J as T,s as $,a9 as V,a7 as _,K as R,b3 as N,b4 as F,al as M,Z as w,ag as U,m as B,a0 as Z,o as s,c as S,w as v,k as c,x as o,d as E,a2 as X,h as Y,q as f,F as q,f as C,n as j,t as O,l as p,a1 as G,a as W,e as H,E as k,b5 as J,aC as Q,r as ee}from"./app-DOhfWUmC.js";import{_ as ne}from"./_plugin-vue_export-helper-DlAUqK2U.js";var te=`
    .p-drawer {
        display: flex;
        flex-direction: column;
        transform: translate3d(0px, 0px, 0px);
        position: relative;
        transition: transform 0.3s;
        background: dt('drawer.background');
        color: dt('drawer.color');
        border-style: solid;
        border-color: dt('drawer.border.color');
        box-shadow: dt('drawer.shadow');
    }

    .p-drawer-content {
        overflow-y: auto;
        flex-grow: 1;
        padding: dt('drawer.content.padding');
    }

    .p-drawer-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        padding: dt('drawer.header.padding');
    }

    .p-drawer-footer {
        padding: dt('drawer.footer.padding');
    }

    .p-drawer-title {
        font-weight: dt('drawer.title.font.weight');
        font-size: dt('drawer.title.font.size');
    }

    .p-drawer-full .p-drawer {
        transition: none;
        transform: none;
        width: 100vw !important;
        height: 100vh !important;
        max-height: 100%;
        top: 0px !important;
        left: 0px !important;
        border-width: 1px;
    }

    .p-drawer-left .p-drawer-enter-active {
        animation: p-animate-drawer-enter-left 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-left .p-drawer-leave-active {
        animation: p-animate-drawer-leave-left 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-right .p-drawer-enter-active {
        animation: p-animate-drawer-enter-right 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-right .p-drawer-leave-active {
        animation: p-animate-drawer-leave-right 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-top .p-drawer-enter-active {
        animation: p-animate-drawer-enter-top 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-top .p-drawer-leave-active {
        animation: p-animate-drawer-leave-top 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-bottom .p-drawer-enter-active {
        animation: p-animate-drawer-enter-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-bottom .p-drawer-leave-active {
        animation: p-animate-drawer-leave-bottom 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }

    .p-drawer-full .p-drawer-enter-active {
        animation: p-animate-drawer-enter-full 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    .p-drawer-full .p-drawer-leave-active {
        animation: p-animate-drawer-leave-full 0.5s cubic-bezier(0.32, 0.72, 0, 1);
    }
    
    .p-drawer-left .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-end-width: 1px;
    }

    .p-drawer-right .p-drawer {
        width: 20rem;
        height: 100%;
        border-inline-start-width: 1px;
    }

    .p-drawer-top .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-end-width: 1px;
    }

    .p-drawer-bottom .p-drawer {
        height: 10rem;
        width: 100%;
        border-block-start-width: 1px;
    }

    .p-drawer-left .p-drawer-content,
    .p-drawer-right .p-drawer-content,
    .p-drawer-top .p-drawer-content,
    .p-drawer-bottom .p-drawer-content {
        width: 100%;
        height: 100%;
    }

    .p-drawer-open {
        display: flex;
    }

    .p-drawer-mask:dir(rtl) {
        flex-direction: row-reverse;
    }

    @keyframes p-animate-drawer-enter-left {
        from {
            transform: translate3d(-100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-left {
        to {
            transform: translate3d(-100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-right {
        from {
            transform: translate3d(100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-right {
        to {
            transform: translate3d(100%, 0px, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-top {
        from {
            transform: translate3d(0px, -100%, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-top {
        to {
            transform: translate3d(0px, -100%, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-bottom {
        from {
            transform: translate3d(0px, 100%, 0px);
        }
    }

    @keyframes p-animate-drawer-leave-bottom {
        to {
            transform: translate3d(0px, 100%, 0px);
        }
    }

    @keyframes p-animate-drawer-enter-full {
        from {
            opacity: 0;
            transform: scale(0.93);
        }
    }

    @keyframes p-animate-drawer-leave-full {
        to {
            opacity: 0;
            transform: scale(0.93);
        }
    }
`,re={mask:function(n){var t=n.position,r=n.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:t==="left"?"flex-start":t==="right"?"flex-end":"center",alignItems:t==="top"?"flex-start":t==="bottom"?"flex-end":"center",pointerEvents:r?"auto":"none"}},root:{pointerEvents:"auto"}},ie={mask:function(n){var t=n.instance,r=n.props,a=["left","right","top","bottom"],i=a.find(function(l){return l===r.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":r.modal,"p-drawer-open":t.containerVisible,"p-drawer-full":t.fullScreen},i?"p-drawer-".concat(i):""]},root:function(n){var t=n.instance;return["p-drawer p-component",{"p-drawer-full":t.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},ae=P.extend({name:"drawer",style:te,classes:ie,inlineStyles:re}),oe={name:"BaseDrawer",extends:_,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:ae,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function h(e){"@babel/helpers - typeof";return h=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},h(e)}function g(e,n,t){return(n=se(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function se(e){var n=le(e,"string");return h(n)=="symbol"?n:n+""}function le(e,n){if(h(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var r=t.call(e,n);if(h(r)!="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var ue={name:"Drawer",extends:oe,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(n){n&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&w.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&w.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&U(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&w.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(n){this.dismissable&&this.modal&&this.mask===n.target&&this.hide()},focus:function(){var n=function(a){return a&&a.querySelector("[autofocus]")},t=this.$slots.header&&n(this.headerContainer);t||(t=this.$slots.default&&n(this.container),t||(t=this.$slots.footer&&n(this.footerContainer),t||(t=this.closeButton))),t&&M(t)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&F()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&N()},onKeydown:function(n){n.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(n){this.container=n},maskRef:function(n){this.mask=n},contentRef:function(n){this.content=n},headerContainerRef:function(n){this.headerContainer=n},footerContainerRef:function(n){this.footerContainer=n},closeButtonRef:function(n){this.closeButton=n?n.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var n=this;this.outsideClickListener||(this.outsideClickListener=function(t){n.isOutsideClicked(t)&&n.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(n){return this.container&&!this.container.contains(n.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return R(g(g(g({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:V},components:{Button:$,Portal:T,TimesIcon:z}},ce=["data-p"],de=["role","aria-modal","data-p"];function fe(e,n,t,r,a,i){var l=B("Button"),u=B("Portal"),b=Z("focustrap");return s(),S(u,null,{default:v(function(){return[a.containerVisible?(s(),c("div",o({key:0,ref:i.maskRef,onMousedown:n[0]||(n[0]=function(){return i.onMaskClick&&i.onMaskClick.apply(i,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":i.dataP},e.ptm("mask")),[E(X,o({name:"p-drawer",onEnter:i.onEnter,onAfterEnter:i.onAfterEnter,onBeforeLeave:i.onBeforeLeave,onLeave:i.onLeave,onAfterLeave:i.onAfterLeave,appear:""},e.ptm("transition")),{default:v(function(){return[e.visible?Y((s(),c("div",o({key:0,ref:i.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":i.dataP},e.ptmi("root")),[e.$slots.container?f(e.$slots,"container",{key:0,closeCallback:i.hide}):(s(),c(q,{key:1},[C("div",o({ref:i.headerContainerRef,class:e.cx("header")},e.ptm("header")),[f(e.$slots,"header",{class:j(e.cx("title"))},function(){return[e.header?(s(),c("div",o({key:0,class:e.cx("title")},e.ptm("title")),O(e.header),17)):p("",!0)]}),e.showCloseIcon?f(e.$slots,"closebutton",{key:0,closeCallback:i.hide},function(){return[E(l,o({ref:i.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":i.closeAriaLabel,unstyled:e.unstyled,onClick:i.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:v(function(L){return[f(e.$slots,"closeicon",{},function(){return[(s(),S(G(e.closeIcon?"span":"TimesIcon"),o({class:[e.closeIcon,L.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):p("",!0)],16),C("div",o({ref:i.contentRef,class:e.cx("content")},e.ptm("content")),[f(e.$slots,"default")],16),e.$slots.footer?(s(),c("div",o({key:0,ref:i.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[f(e.$slots,"footer")],16)):p("",!0)],64))],16,de)),[[b]]):p("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,ce)):p("",!0)]}),_:3})}ue.render=fe;var je=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function Oe(e){return e&&e.__esModule&&Object.prototype.hasOwnProperty.call(e,"default")?e.default:e}const pe={class:"vin-copy-label__text"},me={__name:"VinCopyLabel",props:{vin:{type:String,default:null},size:{type:String,default:"default",validator:e=>["default","compact"].includes(e)},block:{type:Boolean,default:!1}},setup(e){const n=e,t=W(),r=k(()=>n.vin?.trim()||"—"),a=k(()=>!!n.vin?.trim()),i=k(()=>({"vin-copy-label--compact":n.size==="compact","vin-copy-label--block":n.block}));async function l(){const u=n.vin?.trim();if(u)try{await navigator.clipboard.writeText(u),t.add({severity:"success",summary:"تم نسخ رقم الشانصي",life:3e3})}catch{t.add({severity:"error",summary:"تعذر نسخ رقم الشانصي",life:3e3})}}return(u,b)=>(s(),c("div",{class:j(["vin-copy-label",i.value])},[C("span",pe,O(r.value),1),a.value?(s(),S(H($),{key:0,icon:"pi pi-copy",text:"",rounded:"",severity:"secondary",class:"vin-copy-label__btn","aria-label":"نسخ رقم الشانصي",onClick:l})):p("",!0)],2))}},Ke=ne(me,[["__scopeId","data-v-753ae5b4"]]),x=["name","label","title","value","text","port","city"],he=new Set(["urls","keys","images","photos","gallery"]),ye=/(?:^https?:\/\/|\/autos\/|\.(?:jpe?g|png|gif|webp|bmp|svg)(?:\?|$|[#&]))/i;function be(e){if(typeof e!="string")return!0;const n=e.trim();return!n||n.startsWith("{")||n.startsWith("[")?!0:ye.test(n)}function A(e){if(e==null)return null;const n=String(e).trim();return be(n)?null:n}function we(e){return!!(e&&typeof e=="object"&&!Array.isArray(e)&&Array.isArray(e.urls))}function m(e){if(e==null||e==="")return null;if(typeof e=="boolean")return e?"1":"0";if(typeof e=="number"){const t=String(e).trim();return t!==""?t:null}if(typeof e=="string")return A(e);if(typeof e!="object"||Array.isArray(e)&&e.length===0||!Array.isArray(e)&&Object.keys(e).length===0)return null;if(we(e)){for(const t of x)if(Object.prototype.hasOwnProperty.call(e,t)){const r=m(e[t]);if(r!==null)return r}return null}for(const t of x)if(Object.prototype.hasOwnProperty.call(e,t)){const r=m(e[t]);if(r!==null)return r}const n=[];for(const[t,r]of Object.entries(e))if(!he.has(t)){if(r!=null&&(typeof r=="string"||typeof r=="number"||typeof r=="boolean")){const a=A(String(r));a!==null&&n.push(a)}else if(r&&typeof r=="object"){const a=m(r);a!==null&&n.push(a)}}return n.length>0?n.join(", "):null}function d(e,n){return m(e?.raw_data?.[n])}function K(e,n){const t=e?.raw_data?.[n];return t&&typeof t=="object"&&!Array.isArray(t)&&Array.isArray(t.urls)?m(t.name??t.label??t.title??t.port??t.city??null):m(t)}function I(e){if(!e)return null;const n=new Date(e);if(Number.isNaN(n.getTime()))return null;const t=String(n.getDate()).padStart(2,"0"),r=String(n.getMonth()+1).padStart(2,"0"),a=n.getFullYear();return`${t}/${r}/${a}`}function Ie(e){const n=e?.raw_data??e??{};return[n.year??e?.year,n.make??e?.make,n.model??e?.model].filter(Boolean).join(" ")}function ze(e){return d(e,"fuel_type")}function Te(e){const n=e?.toLowerCase()??"";return n.includes("hybrid")?"fuel-hybrid":n.includes("electric")||n==="ev"?"fuel-electric":n.includes("gas")||n.includes("petrol")||n.includes("diesel")?"fuel-gas":"fuel-default"}function Ve(e){return d(e,"lot")}function Ne(e){return d(e,"auction")}function Fe(e){return K(e,"loading_point")}function Me(e){return K(e,"destination")}function Ue(e){return d(e,"status")}function Ze(e){const n=e?.toLowerCase()??"";return n.includes("terminal")?"status-terminal":n.includes("purchase")||n.includes("new")?"status-new":n.includes("shipped")||n.includes("transit")?"status-transit":"status-default"}function Xe(e){return d(e,"container_number")}function Ye(e){return d(e,"booking_number")}const ve=new Set(["no keys","missing","no key","no","false"]);function qe(e){const n=e?.raw_data?.keys;if(n==null)return{label:null,present:null};if(n===!1)return{label:"No Keys",present:!1};if(n===!0)return{label:"Present",present:!0};const t=String(n).trim();if(!t)return{label:null,present:null};const r=t.toLowerCase();return r==="present"?{label:"Present",present:!0}:ve.has(r)?{label:"No Keys",present:!1}:{label:t,present:!0}}function Ge(e){return d(e,"title_status")||"Pending"}function We(e){return I(e?.raw_data?.purchase_date)}function He(e){return I(e?.raw_data?.arrived_terminal_date)}function ke(e){return e?.status==="assigned"||!!e?.active_assignment}function Je(e){return ke(e)?"assignment-pill--assigned":"assignment-pill--unassigned"}const D={vinstack:"المستورد",manual:"اليدوي",nujoom_al_jazeera:"نجوم الجزيرة"};function Qe(e){return D[e]??D.vinstack}function en(e){return e==="manual"?"source-pill--manual":e==="nujoom_al_jazeera"?"source-pill--nujoom":"source-pill--vinstack"}function nn(e){const n=e?.active_assignment?.dealer?.company_name;return n||"Admin"}function tn({enabled:e,hasMore:n,loading:t,onLoadMore:r}){const a=ee(null);let i=null;function l(){i?.disconnect(),i=null}return J(u=>{l(),!(!e.value||!a.value)&&(i=new IntersectionObserver(b=>{!b[0]?.isIntersecting||t.value||!n.value||r()},{root:null,rootMargin:"240px 0px",threshold:0}),i.observe(a.value),u(l))}),Q(l),{sentinel:a}}var ge=`
    .p-skeleton {
        display: block;
        overflow: hidden;
        background: dt('skeleton.background');
        border-radius: dt('skeleton.border.radius');
    }

    .p-skeleton::after {
        content: '';
        animation: p-skeleton-animation 1.2s infinite;
        height: 100%;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        transform: translateX(-100%);
        z-index: 1;
        background: linear-gradient(90deg, rgba(255, 255, 255, 0), dt('skeleton.animation.background'), rgba(255, 255, 255, 0));
    }

    [dir='rtl'] .p-skeleton::after {
        animation-name: p-skeleton-animation-rtl;
    }

    .p-skeleton-circle {
        border-radius: 50%;
    }

    .p-skeleton-animation-none::after {
        animation: none;
    }

    @keyframes p-skeleton-animation {
        from {
            transform: translateX(-100%);
        }
        to {
            transform: translateX(100%);
        }
    }

    @keyframes p-skeleton-animation-rtl {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(-100%);
        }
    }
`,Se={root:{position:"relative"}},Ce={root:function(n){var t=n.props;return["p-skeleton p-component",{"p-skeleton-circle":t.shape==="circle","p-skeleton-animation-none":t.animation==="none"}]}},Le=P.extend({name:"skeleton",style:ge,classes:Ce,inlineStyles:Se}),Be={name:"BaseSkeleton",extends:_,props:{shape:{type:String,default:"rectangle"},size:{type:String,default:null},width:{type:String,default:"100%"},height:{type:String,default:"1rem"},borderRadius:{type:String,default:null},animation:{type:String,default:"wave"}},style:Le,provide:function(){return{$pcSkeleton:this,$parentInstance:this}}};function y(e){"@babel/helpers - typeof";return y=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},y(e)}function Ee(e,n,t){return(n=xe(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function xe(e){var n=Ae(e,"string");return y(n)=="symbol"?n:n+""}function Ae(e,n){if(y(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var r=t.call(e,n);if(y(r)!="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var De={name:"Skeleton",extends:Be,inheritAttrs:!1,computed:{containerStyle:function(){return this.size?{width:this.size,height:this.size,borderRadius:this.borderRadius}:{width:this.width,height:this.height,borderRadius:this.borderRadius}},dataP:function(){return R(Ee({},this.shape,this.shape))}}},Pe=["data-p"];function $e(e,n,t,r,a,i){return s(),c("div",o({class:e.cx("root"),style:[e.sx("root"),i.containerStyle],"aria-hidden":"true"},e.ptmi("root"),{"data-p":i.dataP}),null,16,Pe)}De.render=$e;export{Ke as V,en as a,Ie as b,je as c,ze as d,Te as e,Ve as f,Oe as g,Ne as h,Fe as i,Me as j,Ue as k,Ze as l,Xe as m,Ye as n,qe as o,Ge as p,We as q,He as r,ue as s,nn as t,Je as u,Qe as v,tn as w,De as x,I as y,m as z};
