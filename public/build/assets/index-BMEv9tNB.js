import{B as L,I as _,J as K,s as B,aC as z,ac as x,K as D,aD as A,aF as I,ad as O,Z as h,aE as V,m as S,a0 as T,o as s,c as v,w as b,k as l,t as o,d as C,a2 as N,h as j,q as d,F,f as k,n as $,D as E,l as u,a1 as Z,a as M,e as X,E as y}from"./app-DbYKwEUn.js";import{_ as q}from"./_plugin-vue_export-helper-DlAUqK2U.js";var U=`
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
`,Y={mask:function(n){var t=n.position,a=n.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:t==="left"?"flex-start":t==="right"?"flex-end":"center",alignItems:t==="top"?"flex-start":t==="bottom"?"flex-end":"center",pointerEvents:a?"auto":"none"}},root:{pointerEvents:"auto"}},J={mask:function(n){var t=n.instance,a=n.props,i=["left","right","top","bottom"],r=i.find(function(c){return c===a.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":a.modal,"p-drawer-open":t.containerVisible,"p-drawer-full":t.fullScreen},r?"p-drawer-".concat(r):""]},root:function(n){var t=n.instance;return["p-drawer p-component",{"p-drawer-full":t.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},W=L.extend({name:"drawer",style:U,classes:J,inlineStyles:Y}),G={name:"BaseDrawer",extends:x,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:W,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function p(e){"@babel/helpers - typeof";return p=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},p(e)}function w(e,n,t){return(n=H(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function H(e){var n=Q(e,"string");return p(n)=="symbol"?n:n+""}function Q(e,n){if(p(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var a=t.call(e,n);if(p(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var ee={name:"Drawer",extends:G,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(n){n&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&h.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&h.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&V(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&h.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(n){this.dismissable&&this.modal&&this.mask===n.target&&this.hide()},focus:function(){var n=function(i){return i&&i.querySelector("[autofocus]")},t=this.$slots.header&&n(this.headerContainer);t||(t=this.$slots.default&&n(this.container),t||(t=this.$slots.footer&&n(this.footerContainer),t||(t=this.closeButton))),t&&O(t)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&I()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&A()},onKeydown:function(n){n.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(n){this.container=n},maskRef:function(n){this.mask=n},contentRef:function(n){this.content=n},headerContainerRef:function(n){this.headerContainer=n},footerContainerRef:function(n){this.footerContainer=n},closeButtonRef:function(n){this.closeButton=n?n.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var n=this;this.outsideClickListener||(this.outsideClickListener=function(t){n.isOutsideClicked(t)&&n.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(n){return this.container&&!this.container.contains(n.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return D(w(w(w({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:z},components:{Button:B,Portal:K,TimesIcon:_}},ne=["data-p"],te=["role","aria-modal","data-p"];function re(e,n,t,a,i,r){var c=S("Button"),f=S("Portal"),g=T("focustrap");return s(),v(f,null,{default:b(function(){return[i.containerVisible?(s(),l("div",o({key:0,ref:r.maskRef,onMousedown:n[0]||(n[0]=function(){return r.onMaskClick&&r.onMaskClick.apply(r,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":r.dataP},e.ptm("mask")),[C(N,o({name:"p-drawer",onEnter:r.onEnter,onAfterEnter:r.onAfterEnter,onBeforeLeave:r.onBeforeLeave,onLeave:r.onLeave,onAfterLeave:r.onAfterLeave,appear:""},e.ptm("transition")),{default:b(function(){return[e.visible?j((s(),l("div",o({key:0,ref:r.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":r.dataP},e.ptmi("root")),[e.$slots.container?d(e.$slots,"container",{key:0,closeCallback:r.hide}):(s(),l(F,{key:1},[k("div",o({ref:r.headerContainerRef,class:e.cx("header")},e.ptm("header")),[d(e.$slots,"header",{class:$(e.cx("title"))},function(){return[e.header?(s(),l("div",o({key:0,class:e.cx("title")},e.ptm("title")),E(e.header),17)):u("",!0)]}),e.showCloseIcon?d(e.$slots,"closebutton",{key:0,closeCallback:r.hide},function(){return[C(c,o({ref:r.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":r.closeAriaLabel,unstyled:e.unstyled,onClick:r.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:b(function(R){return[d(e.$slots,"closeicon",{},function(){return[(s(),v(Z(e.closeIcon?"span":"TimesIcon"),o({class:[e.closeIcon,R.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):u("",!0)],16),k("div",o({ref:r.contentRef,class:e.cx("content")},e.ptm("content")),[d(e.$slots,"default")],16),e.$slots.footer?(s(),l("div",o({key:0,ref:r.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[d(e.$slots,"footer")],16)):u("",!0)],64))],16,te)),[[g]]):u("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,ne)):u("",!0)]}),_:3})}ee.render=re;const ae={class:"vin-copy-label__text"},ie={__name:"VinCopyLabel",props:{vin:{type:String,default:null},size:{type:String,default:"default",validator:e=>["default","compact"].includes(e)},block:{type:Boolean,default:!1}},setup(e){const n=e,t=M(),a=y(()=>n.vin?.trim()||"—"),i=y(()=>!!n.vin?.trim()),r=y(()=>({"vin-copy-label--compact":n.size==="compact","vin-copy-label--block":n.block}));async function c(){const f=n.vin?.trim();if(f)try{await navigator.clipboard.writeText(f),t.add({severity:"success",summary:"تم نسخ رقم الشانصي",life:3e3})}catch{t.add({severity:"error",summary:"تعذر نسخ رقم الشانصي",life:3e3})}}return(f,g)=>(s(),l("div",{class:$(["vin-copy-label",r.value])},[k("span",ae,E(a.value),1),i.value?(s(),v(X(B),{key:0,icon:"pi pi-copy",text:"",rounded:"",severity:"secondary",class:"vin-copy-label__btn","aria-label":"نسخ رقم الشانصي",onClick:c})):u("",!0)],2))}},ge=q(ie,[["__scopeId","data-v-753ae5b4"]]);function P(e){if(!e)return null;const n=new Date(e);if(Number.isNaN(n.getTime()))return null;const t=String(n.getDate()).padStart(2,"0"),a=String(n.getMonth()+1).padStart(2,"0"),i=n.getFullYear();return`${t}/${a}/${i}`}function Se(e){const n=e?.raw_data??e??{};return[n.year??e?.year,n.make??e?.make,n.model??e?.model].filter(Boolean).join(" ")}function Ce(e){return e?.raw_data?.fuel_type?.trim()||null}function Le(e){const n=e?.toLowerCase()??"";return n.includes("hybrid")?"fuel-hybrid":n.includes("electric")||n==="ev"?"fuel-electric":n.includes("gas")||n.includes("petrol")||n.includes("diesel")?"fuel-gas":"fuel-default"}function Be(e){return e?.raw_data?.lot?.trim()||null}function xe(e){return e?.raw_data?.auction?.trim()||null}function De(e){return e?.raw_data?.loading_point?.trim()||null}function $e(e){return e?.raw_data?.destination?.trim()||null}function Ee(e){return e?.raw_data?.status?.trim()||null}function Pe(e){const n=e?.toLowerCase()??"";return n.includes("terminal")?"status-terminal":n.includes("purchase")||n.includes("new")?"status-new":n.includes("shipped")||n.includes("transit")?"status-transit":"status-default"}function Re(e){return e?.raw_data?.container_number?.trim()||null}function _e(e){return e?.raw_data?.booking_number?.trim()||null}const oe=new Set(["no keys","missing","no key","no","false"]);function Ke(e){const n=e?.raw_data?.keys;if(n==null)return{label:null,present:null};if(n===!1)return{label:"No Keys",present:!1};if(n===!0)return{label:"Present",present:!0};const t=String(n).trim();if(!t)return{label:null,present:null};const a=t.toLowerCase();return a==="present"?{label:"Present",present:!0}:oe.has(a)?{label:"No Keys",present:!1}:{label:t,present:!0}}function ze(e){return e?.raw_data?.title_status?.trim()||"Pending"}function Ae(e){return P(e?.raw_data?.purchase_date)}function Ie(e){return P(e?.raw_data?.arrived_terminal_date)}function se(e){return e?.status==="assigned"||!!e?.active_assignment}function Oe(e){return se(e)?"assignment-pill--assigned":"assignment-pill--unassigned"}function Ve(e){const n=e?.active_assignment?.dealer?.company_name;return n||"Admin"}var le=`
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
`,de={root:{position:"relative"}},ue={root:function(n){var t=n.props;return["p-skeleton p-component",{"p-skeleton-circle":t.shape==="circle","p-skeleton-animation-none":t.animation==="none"}]}},ce=L.extend({name:"skeleton",style:le,classes:ue,inlineStyles:de}),fe={name:"BaseSkeleton",extends:x,props:{shape:{type:String,default:"rectangle"},size:{type:String,default:null},width:{type:String,default:"100%"},height:{type:String,default:"1rem"},borderRadius:{type:String,default:null},animation:{type:String,default:"wave"}},style:ce,provide:function(){return{$pcSkeleton:this,$parentInstance:this}}};function m(e){"@babel/helpers - typeof";return m=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(n){return typeof n}:function(n){return n&&typeof Symbol=="function"&&n.constructor===Symbol&&n!==Symbol.prototype?"symbol":typeof n},m(e)}function pe(e,n,t){return(n=me(n))in e?Object.defineProperty(e,n,{value:t,enumerable:!0,configurable:!0,writable:!0}):e[n]=t,e}function me(e){var n=he(e,"string");return m(n)=="symbol"?n:n+""}function he(e,n){if(m(e)!="object"||!e)return e;var t=e[Symbol.toPrimitive];if(t!==void 0){var a=t.call(e,n);if(m(a)!="object")return a;throw new TypeError("@@toPrimitive must return a primitive value.")}return(n==="string"?String:Number)(e)}var be={name:"Skeleton",extends:fe,inheritAttrs:!1,computed:{containerStyle:function(){return this.size?{width:this.size,height:this.size,borderRadius:this.borderRadius}:{width:this.width,height:this.height,borderRadius:this.borderRadius}},dataP:function(){return D(pe({},this.shape,this.shape))}}},ye=["data-p"];function we(e,n,t,a,i,r){return s(),l("div",o({class:e.cx("root"),style:[e.sx("root"),r.containerStyle],"aria-hidden":"true"},e.ptmi("root"),{"data-p":r.dataP}),null,16,ye)}be.render=we;export{ge as V,Ce as a,Le as b,Be as c,xe as d,De as e,$e as f,Ee as g,Pe as h,Re as i,_e as j,Ke as k,ze as l,Ae as m,Ie as n,Ve as o,Oe as p,be as q,P as r,ee as s,Se as v};
