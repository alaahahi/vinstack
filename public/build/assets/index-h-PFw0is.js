import{B as D,I as j,J as I,s as P,a9 as z,a7 as $,K as A,b3 as K,b4 as V,al as T,Z as y,ag as N,m as B,a0 as F,o as s,c as g,w,k as c,x as o,d as x,a2 as M,h as Z,q as f,F as U,f as S,n as R,t as O,l as p,a1 as X,a as q,e as Y,E as v,b5 as J,aC as G,r as H}from"./app-DQ-TiAUa.js";import{_ as W}from"./_plugin-vue_export-helper-DlAUqK2U.js";var Q=`
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
`,ee={mask:function(n){var t=n.position,r=n.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:t==="left"?"flex-start":t==="right"?"flex-end":"center",alignItems:t==="top"?"flex-start":t==="bottom"?"flex-end":"center",pointerEvents:r?"auto":"none"}},root:{pointerEvents:"auto"}},ne={mask:function(n){var t=n.instance,r=n.props,a=["left","right","top","bottom"],i=a.find(function(l){return l===r.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":r.modal,"p-drawer-open":t.containerVisible,"p-drawer-full":t.fullScreen},i?"p-drawer-".concat(i):""]},root:function(n){var t=n.instance;return["p-drawer p-component",{"p-drawer-full":t.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},te=D.extend({name:"drawer",style:Q,classes:ne,inlineStyles:ee}),re={name:"BaseDrawer",extends:$,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:te,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function m(e){"@babel/helpers - typeof";return m=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},m(e)}function k(e,n,t){return(n=ie(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function ie(e){var n=ae(e,"string");return m(n)=="symbol"?n:n+""}function ae(e,n){if(m(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var r=t.call(e,n);if(m(r)!="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var oe={name:"Drawer",extends:re,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(n){n&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&y.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&y.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&N(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&y.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(n){this.dismissable&&this.modal&&this.mask===n.target&&this.hide()},focus:function(){var n=function(a){return a&&a.querySelector("[autofocus]")},t=this.$slots.header&&n(this.headerContainer);t||(t=this.$slots.default&&n(this.container),t||(t=this.$slots.footer&&n(this.footerContainer),t||(t=this.closeButton))),t&&T(t)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&V()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&K()},onKeydown:function(n){n.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(n){this.container=n},maskRef:function(n){this.mask=n},contentRef:function(n){this.content=n},headerContainerRef:function(n){this.headerContainer=n},footerContainerRef:function(n){this.footerContainer=n},closeButtonRef:function(n){this.closeButton=n?n.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var n=this;this.outsideClickListener||(this.outsideClickListener=function(t){n.isOutsideClicked(t)&&n.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(n){return this.container&&!this.container.contains(n.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return A(k(k(k({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:z},components:{Button:P,Portal:I,TimesIcon:j}},se=["data-p"],le=["role","aria-modal","data-p"];function ue(e,n,t,r,a,i){var l=B("Button"),d=B("Portal"),b=F("focustrap");return s(),g(d,null,{default:w(function(){return[a.containerVisible?(s(),c("div",o({key:0,ref:i.maskRef,onMousedown:n[0]||(n[0]=function(){return i.onMaskClick&&i.onMaskClick.apply(i,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":i.dataP},e.ptm("mask")),[x(M,o({name:"p-drawer",onEnter:i.onEnter,onAfterEnter:i.onAfterEnter,onBeforeLeave:i.onBeforeLeave,onLeave:i.onLeave,onAfterLeave:i.onAfterLeave,appear:""},e.ptm("transition")),{default:w(function(){return[e.visible?Z((s(),c("div",o({key:0,ref:i.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":i.dataP},e.ptmi("root")),[e.$slots.container?f(e.$slots,"container",{key:0,closeCallback:i.hide}):(s(),c(U,{key:1},[S("div",o({ref:i.headerContainerRef,class:e.cx("header")},e.ptm("header")),[f(e.$slots,"header",{class:R(e.cx("title"))},function(){return[e.header?(s(),c("div",o({key:0,class:e.cx("title")},e.ptm("title")),O(e.header),17)):p("",!0)]}),e.showCloseIcon?f(e.$slots,"closebutton",{key:0,closeCallback:i.hide},function(){return[x(l,o({ref:i.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":i.closeAriaLabel,unstyled:e.unstyled,onClick:i.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:w(function(L){return[f(e.$slots,"closeicon",{},function(){return[(s(),g(X(e.closeIcon?"span":"TimesIcon"),o({class:[e.closeIcon,L.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):p("",!0)],16),S("div",o({ref:i.contentRef,class:e.cx("content")},e.ptm("content")),[f(e.$slots,"default")],16),e.$slots.footer?(s(),c("div",o({key:0,ref:i.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[f(e.$slots,"footer")],16)):p("",!0)],64))],16,le)),[[b]]):p("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,se)):p("",!0)]}),_:3})}oe.render=ue;var De=typeof globalThis<"u"?globalThis:typeof window<"u"?window:typeof global<"u"?global:typeof self<"u"?self:{};function Pe(e){return e&&e.__esModule&&Object.prototype.hasOwnProperty.call(e,"default")?e.default:e}const de={class:"vin-copy-label__text"},ce={__name:"VinCopyLabel",props:{vin:{type:String,default:null},size:{type:String,default:"default",validator:e=>["default","compact"].includes(e)},block:{type:Boolean,default:!1}},setup(e){const n=e,t=q(),r=v(()=>n.vin?.trim()||"—"),a=v(()=>!!n.vin?.trim()),i=v(()=>({"vin-copy-label--compact":n.size==="compact","vin-copy-label--block":n.block}));async function l(){const d=n.vin?.trim();if(d)try{await navigator.clipboard.writeText(d),t.add({severity:"success",summary:"تم نسخ رقم الشانصي",life:3e3})}catch{t.add({severity:"error",summary:"تعذر نسخ رقم الشانصي",life:3e3})}}return(d,b)=>(s(),c("div",{class:R(["vin-copy-label",i.value])},[S("span",de,O(r.value),1),a.value?(s(),g(Y(P),{key:0,icon:"pi pi-copy",text:"",rounded:"",severity:"secondary",class:"vin-copy-label__btn","aria-label":"نسخ رقم الشانصي",onClick:l})):p("",!0)],2))}},$e=W(ce,[["__scopeId","data-v-753ae5b4"]]),fe=["name","label","title","value","text","port","city"];function C(e){if(e==null||e==="")return null;if(typeof e=="boolean")return e?"1":"0";if(typeof e=="number"){const r=String(e).trim();return r!==""?r:null}if(typeof e=="string"){const r=e.trim();return r!==""?r:null}if(typeof e!="object"||Array.isArray(e)&&e.length===0||!Array.isArray(e)&&Object.keys(e).length===0)return null;for(const r of fe)if(Object.prototype.hasOwnProperty.call(e,r)){const a=C(e[r]);if(a!==null)return a}const n=[];for(const r of Object.values(e))if(r!=null&&(typeof r=="string"||typeof r=="number"||typeof r=="boolean")){const a=String(r).trim();a!==""&&n.push(a)}else if(r&&typeof r=="object"){const a=C(r);a!==null&&n.push(a)}if(n.length>0)return n.join(", ");const t=JSON.stringify(e);return t!=="[]"&&t!=="{}"?t:null}function u(e,n){return C(e?.raw_data?.[n])}function _(e){if(!e)return null;const n=new Date(e);if(Number.isNaN(n.getTime()))return null;const t=String(n.getDate()).padStart(2,"0"),r=String(n.getMonth()+1).padStart(2,"0"),a=n.getFullYear();return`${t}/${r}/${a}`}function Ae(e){const n=e?.raw_data??e??{};return[n.year??e?.year,n.make??e?.make,n.model??e?.model].filter(Boolean).join(" ")}function Re(e){return u(e,"fuel_type")}function Oe(e){const n=e?.toLowerCase()??"";return n.includes("hybrid")?"fuel-hybrid":n.includes("electric")||n==="ev"?"fuel-electric":n.includes("gas")||n.includes("petrol")||n.includes("diesel")?"fuel-gas":"fuel-default"}function _e(e){return u(e,"lot")}function je(e){return u(e,"auction")}function Ie(e){return u(e,"loading_point")}function ze(e){return u(e,"destination")}function Ke(e){return u(e,"status")}function Ve(e){const n=e?.toLowerCase()??"";return n.includes("terminal")?"status-terminal":n.includes("purchase")||n.includes("new")?"status-new":n.includes("shipped")||n.includes("transit")?"status-transit":"status-default"}function Te(e){return u(e,"container_number")}function Ne(e){return u(e,"booking_number")}const pe=new Set(["no keys","missing","no key","no","false"]);function Fe(e){const n=e?.raw_data?.keys;if(n==null)return{label:null,present:null};if(n===!1)return{label:"No Keys",present:!1};if(n===!0)return{label:"Present",present:!0};const t=String(n).trim();if(!t)return{label:null,present:null};const r=t.toLowerCase();return r==="present"?{label:"Present",present:!0}:pe.has(r)?{label:"No Keys",present:!1}:{label:t,present:!0}}function Me(e){return u(e,"title_status")||"Pending"}function Ze(e){return _(e?.raw_data?.purchase_date)}function Ue(e){return _(e?.raw_data?.arrived_terminal_date)}function me(e){return e?.status==="assigned"||!!e?.active_assignment}function Xe(e){return me(e)?"assignment-pill--assigned":"assignment-pill--unassigned"}const E={vinstack:"المستورد",manual:"اليدوي",nujoom_al_jazeera:"نجوم الجزيرة"};function qe(e){return E[e]??E.vinstack}function Ye(e){return e==="manual"?"source-pill--manual":e==="nujoom_al_jazeera"?"source-pill--nujoom":"source-pill--vinstack"}function Je(e){const n=e?.active_assignment?.dealer?.company_name;return n||"Admin"}function Ge({enabled:e,hasMore:n,loading:t,onLoadMore:r}){const a=H(null);let i=null;function l(){i?.disconnect(),i=null}return J(d=>{l(),!(!e.value||!a.value)&&(i=new IntersectionObserver(b=>{!b[0]?.isIntersecting||t.value||!n.value||r()},{root:null,rootMargin:"240px 0px",threshold:0}),i.observe(a.value),d(l))}),G(l),{sentinel:a}}var he=`
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
`,be={root:{position:"relative"}},ye={root:function(n){var t=n.props;return["p-skeleton p-component",{"p-skeleton-circle":t.shape==="circle","p-skeleton-animation-none":t.animation==="none"}]}},we=D.extend({name:"skeleton",style:he,classes:ye,inlineStyles:be}),ve={name:"BaseSkeleton",extends:$,props:{shape:{type:String,default:"rectangle"},size:{type:String,default:null},width:{type:String,default:"100%"},height:{type:String,default:"1rem"},borderRadius:{type:String,default:null},animation:{type:String,default:"wave"}},style:we,provide:function(){return{$pcSkeleton:this,$parentInstance:this}}};function h(e){"@babel/helpers - typeof";return h=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},h(e)}function ke(e,n,t){return(n=ge(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function ge(e){var n=Se(e,"string");return h(n)=="symbol"?n:n+""}function Se(e,n){if(h(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var r=t.call(e,n);if(h(r)!="object")return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var Ce={name:"Skeleton",extends:ve,inheritAttrs:!1,computed:{containerStyle:function(){return this.size?{width:this.size,height:this.size,borderRadius:this.borderRadius}:{width:this.width,height:this.height,borderRadius:this.borderRadius}},dataP:function(){return A(ke({},this.shape,this.shape))}}},Le=["data-p"];function Be(e,n,t,r,a,i){return s(),c("div",o({class:e.cx("root"),style:[e.sx("root"),i.containerStyle],"aria-hidden":"true"},e.ptmi("root"),{"data-p":i.dataP}),null,16,Le)}Ce.render=Be;export{$e as V,Ye as a,Ae as b,De as c,Re as d,Oe as e,_e as f,Pe as g,je as h,Ie as i,ze as j,Ke as k,Ve as l,Te as m,Ne as n,Fe as o,Me as p,Ze as q,Ue as r,oe as s,Je as t,Xe as u,qe as v,Ge as w,Ce as x,_ as y,C as z};
