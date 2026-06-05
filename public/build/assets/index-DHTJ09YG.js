import{B,I as _,J as I,s as x,as as K,ac as D,K as $,at as z,av as A,ad as O,Z as b,au as V,m as C,a0 as T,o as s,c as k,w as y,k as u,x as o,d as L,a2 as N,h as j,q as c,F,f as g,n as E,t as P,l as f,a1 as M,a as Z,e as X,E as v,aR as U,a7 as q,r as Y}from"./app-Dyb3oCSc.js";import{_ as J}from"./_plugin-vue_export-helper-DlAUqK2U.js";var W=`
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
`,G={mask:function(n){var t=n.position,a=n.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:t==="left"?"flex-start":t==="right"?"flex-end":"center",alignItems:t==="top"?"flex-start":t==="bottom"?"flex-end":"center",pointerEvents:a?"auto":"none"}},root:{pointerEvents:"auto"}},H={mask:function(n){var t=n.instance,a=n.props,i=["left","right","top","bottom"],r=i.find(function(l){return l===a.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":a.modal,"p-drawer-open":t.containerVisible,"p-drawer-full":t.fullScreen},r?"p-drawer-".concat(r):""]},root:function(n){var t=n.instance;return["p-drawer p-component",{"p-drawer-full":t.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},Q=B.extend({name:"drawer",style:W,classes:H,inlineStyles:G}),ee={name:"BaseDrawer",extends:D,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:Q,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function p(e){"@babel/helpers - typeof";return p=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},p(e)}function w(e,n,t){return(n=ne(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function ne(e){var n=te(e,"string");return p(n)=="symbol"?n:n+""}function te(e,n){if(p(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var a=t.call(e,n);if(p(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var re={name:"Drawer",extends:ee,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(n){n&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&b.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&b.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&V(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&b.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(n){this.dismissable&&this.modal&&this.mask===n.target&&this.hide()},focus:function(){var n=function(i){return i&&i.querySelector("[autofocus]")},t=this.$slots.header&&n(this.headerContainer);t||(t=this.$slots.default&&n(this.container),t||(t=this.$slots.footer&&n(this.footerContainer),t||(t=this.closeButton))),t&&O(t)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&A()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&z()},onKeydown:function(n){n.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(n){this.container=n},maskRef:function(n){this.mask=n},contentRef:function(n){this.content=n},headerContainerRef:function(n){this.headerContainer=n},footerContainerRef:function(n){this.footerContainer=n},closeButtonRef:function(n){this.closeButton=n?n.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var n=this;this.outsideClickListener||(this.outsideClickListener=function(t){n.isOutsideClicked(t)&&n.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(n){return this.container&&!this.container.contains(n.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return $(w(w(w({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:K},components:{Button:x,Portal:I,TimesIcon:_}},ae=["data-p"],ie=["role","aria-modal","data-p"];function oe(e,n,t,a,i,r){var l=C("Button"),d=C("Portal"),h=T("focustrap");return s(),k(d,null,{default:y(function(){return[i.containerVisible?(s(),u("div",o({key:0,ref:r.maskRef,onMousedown:n[0]||(n[0]=function(){return r.onMaskClick&&r.onMaskClick.apply(r,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":r.dataP},e.ptm("mask")),[L(N,o({name:"p-drawer",onEnter:r.onEnter,onAfterEnter:r.onAfterEnter,onBeforeLeave:r.onBeforeLeave,onLeave:r.onLeave,onAfterLeave:r.onAfterLeave,appear:""},e.ptm("transition")),{default:y(function(){return[e.visible?j((s(),u("div",o({key:0,ref:r.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":r.dataP},e.ptmi("root")),[e.$slots.container?c(e.$slots,"container",{key:0,closeCallback:r.hide}):(s(),u(F,{key:1},[g("div",o({ref:r.headerContainerRef,class:e.cx("header")},e.ptm("header")),[c(e.$slots,"header",{class:E(e.cx("title"))},function(){return[e.header?(s(),u("div",o({key:0,class:e.cx("title")},e.ptm("title")),P(e.header),17)):f("",!0)]}),e.showCloseIcon?c(e.$slots,"closebutton",{key:0,closeCallback:r.hide},function(){return[L(l,o({ref:r.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":r.closeAriaLabel,unstyled:e.unstyled,onClick:r.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:y(function(S){return[c(e.$slots,"closeicon",{},function(){return[(s(),k(M(e.closeIcon?"span":"TimesIcon"),o({class:[e.closeIcon,S.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):f("",!0)],16),g("div",o({ref:r.contentRef,class:e.cx("content")},e.ptm("content")),[c(e.$slots,"default")],16),e.$slots.footer?(s(),u("div",o({key:0,ref:r.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[c(e.$slots,"footer")],16)):f("",!0)],64))],16,ie)),[[h]]):f("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,ae)):f("",!0)]}),_:3})}re.render=oe;const se={class:"vin-copy-label__text"},le={__name:"VinCopyLabel",props:{vin:{type:String,default:null},size:{type:String,default:"default",validator:e=>["default","compact"].includes(e)},block:{type:Boolean,default:!1}},setup(e){const n=e,t=Z(),a=v(()=>n.vin?.trim()||"—"),i=v(()=>!!n.vin?.trim()),r=v(()=>({"vin-copy-label--compact":n.size==="compact","vin-copy-label--block":n.block}));async function l(){const d=n.vin?.trim();if(d)try{await navigator.clipboard.writeText(d),t.add({severity:"success",summary:"تم نسخ رقم الشانصي",life:3e3})}catch{t.add({severity:"error",summary:"تعذر نسخ رقم الشانصي",life:3e3})}}return(d,h)=>(s(),u("div",{class:E(["vin-copy-label",r.value])},[g("span",se,P(a.value),1),i.value?(s(),k(X(x),{key:0,icon:"pi pi-copy",text:"",rounded:"",severity:"secondary",class:"vin-copy-label__btn","aria-label":"نسخ رقم الشانصي",onClick:l})):f("",!0)],2))}},Le=J(le,[["__scopeId","data-v-753ae5b4"]]);function R(e){if(!e)return null;const n=new Date(e);if(Number.isNaN(n.getTime()))return null;const t=String(n.getDate()).padStart(2,"0"),a=String(n.getMonth()+1).padStart(2,"0"),i=n.getFullYear();return`${t}/${a}/${i}`}function Be(e){const n=e?.raw_data??e??{};return[n.year??e?.year,n.make??e?.make,n.model??e?.model].filter(Boolean).join(" ")}function xe(e){return e?.raw_data?.fuel_type?.trim()||null}function De(e){const n=e?.toLowerCase()??"";return n.includes("hybrid")?"fuel-hybrid":n.includes("electric")||n==="ev"?"fuel-electric":n.includes("gas")||n.includes("petrol")||n.includes("diesel")?"fuel-gas":"fuel-default"}function $e(e){return e?.raw_data?.lot?.trim()||null}function Ee(e){return e?.raw_data?.auction?.trim()||null}function Pe(e){return e?.raw_data?.loading_point?.trim()||null}function Re(e){return e?.raw_data?.destination?.trim()||null}function _e(e){return e?.raw_data?.status?.trim()||null}function Ie(e){const n=e?.toLowerCase()??"";return n.includes("terminal")?"status-terminal":n.includes("purchase")||n.includes("new")?"status-new":n.includes("shipped")||n.includes("transit")?"status-transit":"status-default"}function Ke(e){return e?.raw_data?.container_number?.trim()||null}function ze(e){return e?.raw_data?.booking_number?.trim()||null}const de=new Set(["no keys","missing","no key","no","false"]);function Ae(e){const n=e?.raw_data?.keys;if(n==null)return{label:null,present:null};if(n===!1)return{label:"No Keys",present:!1};if(n===!0)return{label:"Present",present:!0};const t=String(n).trim();if(!t)return{label:null,present:null};const a=t.toLowerCase();return a==="present"?{label:"Present",present:!0}:de.has(a)?{label:"No Keys",present:!1}:{label:t,present:!0}}function Oe(e){return e?.raw_data?.title_status?.trim()||"Pending"}function Ve(e){return R(e?.raw_data?.purchase_date)}function Te(e){return R(e?.raw_data?.arrived_terminal_date)}function ue(e){return e?.status==="assigned"||!!e?.active_assignment}function Ne(e){return ue(e)?"assignment-pill--assigned":"assignment-pill--unassigned"}function je(e){const n=e?.active_assignment?.dealer?.company_name;return n||"Admin"}function Fe({enabled:e,hasMore:n,loading:t,onLoadMore:a}){const i=Y(null);let r=null;function l(){r?.disconnect(),r=null}return U(d=>{l(),!(!e.value||!i.value)&&(r=new IntersectionObserver(h=>{!h[0]?.isIntersecting||t.value||!n.value||a()},{root:null,rootMargin:"240px 0px",threshold:0}),r.observe(i.value),d(l))}),q(l),{sentinel:i}}var ce=`
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
`,fe={root:{position:"relative"}},pe={root:function(n){var t=n.props;return["p-skeleton p-component",{"p-skeleton-circle":t.shape==="circle","p-skeleton-animation-none":t.animation==="none"}]}},me=B.extend({name:"skeleton",style:ce,classes:pe,inlineStyles:fe}),he={name:"BaseSkeleton",extends:D,props:{shape:{type:String,default:"rectangle"},size:{type:String,default:null},width:{type:String,default:"100%"},height:{type:String,default:"1rem"},borderRadius:{type:String,default:null},animation:{type:String,default:"wave"}},style:me,provide:function(){return{$pcSkeleton:this,$parentInstance:this}}};function m(e){"@babel/helpers - typeof";return m=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},m(e)}function be(e,n,t){return(n=ye(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function ye(e){var n=ve(e,"string");return m(n)=="symbol"?n:n+""}function ve(e,n){if(m(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var a=t.call(e,n);if(m(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var we={name:"Skeleton",extends:he,inheritAttrs:!1,computed:{containerStyle:function(){return this.size?{width:this.size,height:this.size,borderRadius:this.borderRadius}:{width:this.width,height:this.height,borderRadius:this.borderRadius}},dataP:function(){return $(be({},this.shape,this.shape))}}},ke=["data-p"];function ge(e,n,t,a,i,r){return s(),u("div",o({class:e.cx("root"),style:[e.sx("root"),r.containerStyle],"aria-hidden":"true"},e.ptmi("root"),{"data-p":r.dataP}),null,16,ke)}we.render=ge;export{Le as V,xe as a,De as b,$e as c,Ee as d,Pe as e,Re as f,_e as g,Ie as h,Ke as i,ze as j,Ae as k,Oe as l,Ve as m,Te as n,je as o,Ne as p,we as q,R as r,re as s,Fe as u,Be as v};
