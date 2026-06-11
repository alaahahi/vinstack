import{a as qe}from"./index-BXCOqyD_.js";import{B as Le,I as We,J as Je,s as O,af as Ye,a9 as Qe,K as Ie,ag as Xe,ah as et,aa as tt,Z as de,ai as nt,m as be,a0 as at,o as i,c as E,w as X,k as l,x as U,d as L,a2 as it,h as lt,q as W,F as R,f as n,n as T,t as h,l as y,a1 as st,i as M,g as ee,e as I,E as b,p as K,aj as ue,r as C,a6 as Se,a as rt,D as ot,j as dt,y as ve}from"./app-CtGfrpU3.js";import{V as ze,e as De,v as ut,f as ct,g as pt,h as vt,i as ft,j as mt,k as ht,l as yt,m as Pe,n as gt,o as bt,p as kt,q as wt,r as $t,t as xt,u as Ct,w as Lt,x as It,y as St,C as zt,z as Dt,G as Pt,_ as ke,A as we,B as Bt,D as ce,E as _t,F as Vt,H as At,I as Et,J as Rt,K as Ut,L as Tt,M as Mt,N as Ot,O as $e,P as xe}from"./useInfiniteScroll-cpBmERC4.js";import{s as ae}from"./index-Ct_8t2sQ.js";import{s as Be}from"./index-BmfcuscG.js";import{_ as ie}from"./_plugin-vue_export-helper-DlAUqK2U.js";var Zt=`
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
`,Ft={mask:function(t){var a=t.position,v=t.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:a==="left"?"flex-start":a==="right"?"flex-end":"center",alignItems:a==="top"?"flex-start":a==="bottom"?"flex-end":"center",pointerEvents:v?"auto":"none"}},root:{pointerEvents:"auto"}},jt={mask:function(t){var a=t.instance,v=t.props,g=["left","right","top","bottom"],p=g.find(function(u){return u===v.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":v.modal,"p-drawer-open":a.containerVisible,"p-drawer-full":a.fullScreen},p?"p-drawer-".concat(p):""]},root:function(t){var a=t.instance;return["p-drawer p-component",{"p-drawer-full":a.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},Nt=Le.extend({name:"drawer",style:Zt,classes:jt,inlineStyles:Ft}),Gt={name:"BaseDrawer",extends:Qe,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:Nt,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function te(e){"@babel/helpers - typeof";return te=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},te(e)}function pe(e,t,a){return(t=Kt(t))in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function Kt(e){var t=Ht(e,"string");return te(t)=="symbol"?t:t+""}function Ht(e,t){if(te(e)!="object"||!e)return e;var a=e[Symbol.toPrimitive];if(a!==void 0){var v=a.call(e,t);if(te(v)!="object")return v;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var _e={name:"Drawer",extends:Gt,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(t){t&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&de.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&de.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&nt(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&de.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(t){this.dismissable&&this.modal&&this.mask===t.target&&this.hide()},focus:function(){var t=function(g){return g&&g.querySelector("[autofocus]")},a=this.$slots.header&&t(this.headerContainer);a||(a=this.$slots.default&&t(this.container),a||(a=this.$slots.footer&&t(this.footerContainer),a||(a=this.closeButton))),a&&tt(a)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&et()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&Xe()},onKeydown:function(t){t.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(t){this.container=t},maskRef:function(t){this.mask=t},contentRef:function(t){this.content=t},headerContainerRef:function(t){this.headerContainer=t},footerContainerRef:function(t){this.footerContainer=t},closeButtonRef:function(t){this.closeButton=t?t.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var t=this;this.outsideClickListener||(this.outsideClickListener=function(a){t.isOutsideClicked(a)&&t.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(t){return this.container&&!this.container.contains(t.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return Ie(pe(pe(pe({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:Ye},components:{Button:O,Portal:Je,TimesIcon:We}},qt=["data-p"],Wt=["role","aria-modal","data-p"];function Jt(e,t,a,v,g,p){var u=be("Button"),D=be("Portal"),A=at("focustrap");return i(),E(D,null,{default:X(function(){return[g.containerVisible?(i(),l("div",U({key:0,ref:p.maskRef,onMousedown:t[0]||(t[0]=function(){return p.onMaskClick&&p.onMaskClick.apply(p,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":p.dataP},e.ptm("mask")),[L(it,U({name:"p-drawer",onEnter:p.onEnter,onAfterEnter:p.onAfterEnter,onBeforeLeave:p.onBeforeLeave,onLeave:p.onLeave,onAfterLeave:p.onAfterLeave,appear:""},e.ptm("transition")),{default:X(function(){return[e.visible?lt((i(),l("div",U({key:0,ref:p.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":p.dataP},e.ptmi("root")),[e.$slots.container?W(e.$slots,"container",{key:0,closeCallback:p.hide}):(i(),l(R,{key:1},[n("div",U({ref:p.headerContainerRef,class:e.cx("header")},e.ptm("header")),[W(e.$slots,"header",{class:T(e.cx("title"))},function(){return[e.header?(i(),l("div",U({key:0,class:e.cx("title")},e.ptm("title")),h(e.header),17)):y("",!0)]}),e.showCloseIcon?W(e.$slots,"closebutton",{key:0,closeCallback:p.hide},function(){return[L(u,U({ref:p.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":p.closeAriaLabel,unstyled:e.unstyled,onClick:p.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:X(function($){return[W(e.$slots,"closeicon",{},function(){return[(i(),E(st(e.closeIcon?"span":"TimesIcon"),U({class:[e.closeIcon,$.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):y("",!0)],16),n("div",U({ref:p.contentRef,class:e.cx("content")},e.ptm("content")),[W(e.$slots,"default")],16),e.$slots.footer?(i(),l("div",U({key:0,ref:p.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[W(e.$slots,"footer")],16)):y("",!0)],64))],16,Wt)),[[A]]):y("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,qt)):y("",!0)]}),_:3})}_e.render=Jt;var Yt=`
    .p-textarea {
        font-family: inherit;
        font-feature-settings: inherit;
        font-size: 1rem;
        color: dt('textarea.color');
        background: dt('textarea.background');
        padding-block: dt('textarea.padding.y');
        padding-inline: dt('textarea.padding.x');
        border: 1px solid dt('textarea.border.color');
        transition:
            background dt('textarea.transition.duration'),
            color dt('textarea.transition.duration'),
            border-color dt('textarea.transition.duration'),
            outline-color dt('textarea.transition.duration'),
            box-shadow dt('textarea.transition.duration');
        appearance: none;
        border-radius: dt('textarea.border.radius');
        outline-color: transparent;
        box-shadow: dt('textarea.shadow');
    }

    .p-textarea:enabled:hover {
        border-color: dt('textarea.hover.border.color');
    }

    .p-textarea:enabled:focus {
        border-color: dt('textarea.focus.border.color');
        box-shadow: dt('textarea.focus.ring.shadow');
        outline: dt('textarea.focus.ring.width') dt('textarea.focus.ring.style') dt('textarea.focus.ring.color');
        outline-offset: dt('textarea.focus.ring.offset');
    }

    .p-textarea.p-invalid {
        border-color: dt('textarea.invalid.border.color');
    }

    .p-textarea.p-variant-filled {
        background: dt('textarea.filled.background');
    }

    .p-textarea.p-variant-filled:enabled:hover {
        background: dt('textarea.filled.hover.background');
    }

    .p-textarea.p-variant-filled:enabled:focus {
        background: dt('textarea.filled.focus.background');
    }

    .p-textarea:disabled {
        opacity: 1;
        background: dt('textarea.disabled.background');
        color: dt('textarea.disabled.color');
    }

    .p-textarea::placeholder {
        color: dt('textarea.placeholder.color');
    }

    .p-textarea.p-invalid::placeholder {
        color: dt('textarea.invalid.placeholder.color');
    }

    .p-textarea-fluid {
        width: 100%;
    }

    .p-textarea-resizable {
        overflow: hidden;
        resize: none;
    }

    .p-textarea-sm {
        font-size: dt('textarea.sm.font.size');
        padding-block: dt('textarea.sm.padding.y');
        padding-inline: dt('textarea.sm.padding.x');
    }

    .p-textarea-lg {
        font-size: dt('textarea.lg.font.size');
        padding-block: dt('textarea.lg.padding.y');
        padding-inline: dt('textarea.lg.padding.x');
    }
`,Qt={root:function(t){var a=t.instance,v=t.props;return["p-textarea p-component",{"p-filled":a.$filled,"p-textarea-resizable ":v.autoResize,"p-textarea-sm p-inputfield-sm":v.size==="small","p-textarea-lg p-inputfield-lg":v.size==="large","p-invalid":a.$invalid,"p-variant-filled":a.$variant==="filled","p-textarea-fluid":a.$fluid}]}},Xt=Le.extend({name:"textarea",style:Yt,classes:Qt}),en={name:"BaseTextarea",extends:qe,props:{autoResize:Boolean},style:Xt,provide:function(){return{$pcTextarea:this,$parentInstance:this}}};function ne(e){"@babel/helpers - typeof";return ne=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ne(e)}function tn(e,t,a){return(t=nn(t))in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function nn(e){var t=an(e,"string");return ne(t)=="symbol"?t:t+""}function an(e,t){if(ne(e)!="object"||!e)return e;var a=e[Symbol.toPrimitive];if(a!==void 0){var v=a.call(e,t);if(ne(v)!="object")return v;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var ln={name:"Textarea",extends:en,inheritAttrs:!1,observer:null,mounted:function(){var t=this;this.autoResize&&(this.observer=new ResizeObserver(function(){requestAnimationFrame(function(){t.resize()})}),this.observer.observe(this.$el))},updated:function(){this.autoResize&&this.resize()},beforeUnmount:function(){this.observer&&this.observer.disconnect()},methods:{resize:function(){if(this.$el.offsetParent){var t=this.$el.style.height,a=parseInt(t)||0,v=this.$el.scrollHeight,g=!a||v>a,p=a&&v<a;p?(this.$el.style.height="auto",this.$el.style.height="".concat(this.$el.scrollHeight,"px")):g&&(this.$el.style.height="".concat(v,"px"))}},onInput:function(t){this.autoResize&&this.resize(),this.writeValue(t.target.value,t)}},computed:{attrs:function(){return U(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return Ie(tn({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},sn=["value","name","disabled","aria-invalid","data-p"];function rn(e,t,a,v,g,p){return i(),l("textarea",U({class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.invalid||void 0,"data-p":p.dataP,onInput:t[0]||(t[0]=function(){return p.onInput&&p.onInput.apply(p,arguments)})},p.attrs),null,16,sn)}ln.render=rn;const on={class:"vehicle-row"},dn={class:"cell cell-vehicle"},un={class:"vehicle-info"},cn={class:"title-line"},pn={key:1,class:"pi pi-images local-upload-hint",title:"يوجد صور مرفوعة محلياً للتاجر","aria-label":"صور مرفوعة محلياً"},vn={class:"entered-by"},fn={class:"cell cell-lot"},mn={class:"lot-id"},hn={class:"auction"},yn={class:"cell cell-route"},gn={key:0,class:"route-line"},bn={key:1,class:"route-line"},kn={class:"cell cell-refs"},wn={key:0,class:"ref-line ref-line--container"},$n={class:"ref-container-num"},xn={key:1,class:"ref-line"},Cn={class:"ref-badges"},Ln={class:"mini-badge mini-badge--neutral"},In={class:"cell cell-dates"},Sn={class:"date-row"},zn={class:"date-value"},Dn={class:"date-row"},Pn={class:"date-value"},Bn={key:0,class:"cell cell-admin"},_n={key:1,class:"dealer-tag"},Vn={class:"dealer-tag__name"},An={key:1,class:"cell cell-actions"},En={__name:"VehicleListRow",props:{vehicle:{type:Object,required:!0},mode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)},trackingAvailable:{type:Boolean,default:!1}},emits:["assign","update-status","open-detail","edit","unassign","track-container"],setup(e){const t=e,a=b(()=>t.vehicle?.source==="manual"),v=b(()=>a.value?"يدوية":"مستوردة"),g=b(()=>a.value?"source-pill--manual":"source-pill--vinstack"),p=b(()=>ut(t.vehicle)),u=b(()=>(t.vehicle?.uploaded_images?.length??0)>0),D=b(()=>ct(t.vehicle)),A=b(()=>pt(D.value)),$=b(()=>vt(t.vehicle)),m=b(()=>ft(t.vehicle)),P=b(()=>mt(t.vehicle)),S=b(()=>ht(t.vehicle)),V=b(()=>yt(t.vehicle)),z=b(()=>Pe(V.value)),j=b(()=>gt(t.vehicle)),N=b(()=>j.value?t.trackingAvailable:!1),x=b(()=>bt(t.vehicle)),Z=b(()=>kt(t.vehicle)),G=b(()=>wt(t.vehicle)),F=b(()=>$t(t.vehicle)),o=b(()=>xt(t.vehicle)),k=b(()=>Ct(t.vehicle)),w=b(()=>t.vehicle.active_assignment?.dealer?.company_name??null),B=b(()=>Lt(t.vehicle));return(_,f)=>(i(),l("div",on,[n("div",dn,[L(ze,{vehicle:e.vehicle,variant:"row"},null,8,["vehicle"]),n("div",un,[n("div",cn,[n("button",{type:"button",class:"title title-link",onClick:f[0]||(f[0]=H=>_.$emit("open-detail",e.vehicle))},h(p.value||"—"),1),D.value?(i(),l("span",{key:0,class:T(["fuel-badge",A.value])},h(D.value),3)):y("",!0),n("span",{class:T(["source-pill",g.value])},h(v.value),3),e.mode==="admin"&&u.value?(i(),l("i",pn)):y("",!0)]),L(De,{vin:e.vehicle.vin,class:"vehicle-vin-line"},null,8,["vin"]),n("div",vn,"Entered by "+h(k.value),1)])]),n("div",fn,[n("div",mn,h($.value||"—"),1),n("div",hn,h(m.value||"—"),1)]),n("div",yn,[P.value?(i(),l("div",gn,[f[6]||(f[6]=n("span",{class:"route-dot route-dot--origin"},null,-1)),n("span",null,h(P.value),1)])):y("",!0),S.value?(i(),l("div",bn,[f[7]||(f[7]=n("i",{class:"pi pi-map-marker route-pin"},null,-1)),n("span",null,h(S.value),1)])):y("",!0),V.value?(i(),l("span",{key:2,class:T(["status-pill",z.value])},[f[8]||(f[8]=n("span",{class:"status-dot"},null,-1)),M(" "+h(V.value),1)],2)):y("",!0)]),n("div",kn,[j.value?(i(),l("div",wn,[f[9]||(f[9]=n("i",{class:"pi pi-box ref-icon"},null,-1)),n("span",$n,h(j.value),1),L(I(O),{icon:"pi pi-map-marker",severity:N.value?"info":"secondary",text:"",rounded:"",size:"small",disabled:!N.value,class:T(["track-btn",{"track-btn--ready":N.value}]),"aria-label":"تتبع الحاوية",title:"تتبع الحاوية",onClick:f[1]||(f[1]=ee(H=>_.$emit("track-container",e.vehicle),["stop"]))},null,8,["severity","disabled","class"])])):y("",!0),x.value?(i(),l("div",xn,[f[10]||(f[10]=n("i",{class:"pi pi-file ref-icon"},null,-1)),n("span",null,h(x.value),1)])):y("",!0),n("div",Cn,[Z.value.label?(i(),l("span",{key:0,class:T(["mini-badge",Z.value.present?"mini-badge--ok":"mini-badge--bad"])},[f[11]||(f[11]=n("i",{class:"pi pi-key"},null,-1)),M(" "+h(Z.value.label),1)],2)):y("",!0),n("span",Ln,[f[12]||(f[12]=n("i",{class:"pi pi-file"},null,-1)),M(" "+h(G.value),1)])])]),n("div",In,[n("div",Sn,[f[13]||(f[13]=n("span",{class:"date-label"},"Purchase",-1)),n("span",zn,h(F.value||"—"),1)]),n("div",Dn,[f[14]||(f[14]=n("span",{class:"date-label"},"Arrived terminal",-1)),n("span",Pn,h(o.value||"—"),1)])]),e.mode==="admin"?(i(),l("div",Bn,[e.vehicle.status?(i(),l("span",{key:0,class:T(["status-pill assignment-pill",B.value])},[f[15]||(f[15]=n("span",{class:"status-dot"},null,-1)),M(" "+h(e.vehicle.status),1)],2)):y("",!0),w.value?(i(),l("div",_n,[n("span",Vn,h(w.value),1),n("button",{type:"button",class:"dealer-tag__remove",title:"إلغاء الإسناد","aria-label":"إلغاء إسناد التاجر",onClick:f[2]||(f[2]=ee(H=>_.$emit("unassign",e.vehicle),["stop"]))},[...f[16]||(f[16]=[n("i",{class:"pi pi-times"},null,-1)])])])):y("",!0),a.value?(i(),E(I(O),{key:2,icon:"pi pi-pencil",label:"تعديل",size:"small",severity:"secondary",outlined:"",title:"تعديل سيارة يدوية",onClick:f[3]||(f[3]=H=>_.$emit("edit",e.vehicle))})):y("",!0),L(I(O),{label:"إسناد",size:"small",class:"btn-assign",onClick:f[4]||(f[4]=H=>_.$emit("assign",e.vehicle))})])):(i(),l("div",An,[L(I(Be),{value:e.vehicle.status,class:"local-tag"},null,8,["value"]),L(I(O),{icon:"pi pi-pencil",severity:"secondary",text:"",rounded:"",title:"تحديث الحالة",onClick:f[5]||(f[5]=H=>_.$emit("update-status",e.vehicle))})]))]))}},Rn=ie(En,[["__scopeId","data-v-0f42a405"]]),Un={class:"vehicle-list-panel"},Tn={key:0,class:"list-header"},Mn={key:1,class:"list-loading"},On={key:2,class:"list-empty"},Zn={key:0,class:"empty-hint"},Fn={key:3,class:"list-body"},jn={__name:"VehicleListPanel",props:{vehicles:{type:Array,default:()=>[]},loading:{type:Boolean,default:!1},loadingMore:{type:Boolean,default:!1},total:{type:Number,default:0},page:{type:Number,default:1},perPage:{type:Number,default:50},mode:{type:String,default:"admin"},showHeader:{type:Boolean,default:!0},emptyText:{type:String,default:"لا توجد سيارات مسندة إليك"},emptyHint:{type:String,default:""},emptyActionLabel:{type:String,default:""},infiniteScroll:{type:Boolean,default:!1},hasMore:{type:Boolean,default:!1},trackingAvailable:{type:Boolean,default:!1}},emits:["assign","unassign","update-status","open-detail","edit","page","empty-action","load-more"],setup(e,{emit:t}){const a=e,v=t,{sentinel:g}=It({enabled:ue(a,"infiniteScroll"),hasMore:ue(a,"hasMore"),loading:ue(a,"loadingMore"),onLoadMore:()=>v("load-more")}),p=g,u=C(!1),D=C(null);function A($){const m=Dt($);m&&(D.value=m,u.value=!0)}return($,m)=>(i(),l("div",Un,[e.showHeader?(i(),l("div",Tn,[m[8]||(m[8]=n("span",null,"Vehicle",-1)),m[9]||(m[9]=n("span",null,"ID & source",-1)),m[10]||(m[10]=n("span",null,"Route & status",-1)),m[11]||(m[11]=n("span",null,"References",-1)),m[12]||(m[12]=n("span",null,"Dates",-1)),n("span",null,h(e.mode==="admin"?"Assignment":"Actions"),1)])):y("",!0),e.loading&&!e.vehicles.length?(i(),l("div",Mn,[L(I(ae),{style:{width:"36px",height:"36px"}})])):e.vehicles.length?(i(),l("div",Fn,[(i(!0),l(R,null,K(e.vehicles,P=>(i(),E(Rn,{key:P.id,vehicle:P,mode:e.mode,"tracking-available":e.trackingAvailable,onAssign:m[1]||(m[1]=S=>$.$emit("assign",S)),onUnassign:m[2]||(m[2]=S=>$.$emit("unassign",S)),onUpdateStatus:m[3]||(m[3]=S=>$.$emit("update-status",S)),onOpenDetail:m[4]||(m[4]=S=>$.$emit("open-detail",S)),onEdit:m[5]||(m[5]=S=>$.$emit("edit",S)),onTrackContainer:A},null,8,["vehicle","mode","tracking-available"]))),128)),e.infiniteScroll&&e.hasMore?(i(),l("div",{key:0,ref_key:"sentinelRef",ref:p,class:"list-sentinel","aria-hidden":"true"},[e.loadingMore?(i(),E(I(ae),{key:0,style:{width:"28px",height:"28px"}})):y("",!0)],512)):y("",!0)])):(i(),l("div",On,[m[13]||(m[13]=n("i",{class:"pi pi-car"},null,-1)),n("span",null,h(e.emptyText),1),e.emptyHint?(i(),l("p",Zn,h(e.emptyHint),1)):y("",!0),e.emptyActionLabel?(i(),E(I(O),{key:1,label:e.emptyActionLabel,icon:"pi pi-refresh",outlined:"",size:"small",onClick:m[0]||(m[0]=P=>$.$emit("empty-action"))},null,8,["label"])):y("",!0)])),!e.infiniteScroll&&e.total>e.perPage?(i(),E(I(St),{key:4,rows:e.perPage,"total-records":e.total,first:(e.page-1)*e.perPage,template:"FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink",class:"list-paginator",onPage:m[6]||(m[6]=P=>$.$emit("page",P))},null,8,["rows","total-records","first"])):y("",!0),e.trackingAvailable?(i(),E(zt,{key:5,visible:u.value,"onUpdate:visible":m[7]||(m[7]=P=>u.value=P),"api-role":e.mode,container:D.value},null,8,["visible","api-role","container"])):y("",!0)]))}},ui=ie(jn,[["__scopeId","data-v-592b36b5"]]);function Nn(e){const t=e?.response?.data;if(!t)return e?.message||"تعذّر رفع ملف ZIP إلى Vinstack";if(t.errors&&typeof t.errors=="object"){const g=Object.values(t.errors).flat()[0];if(g)return String(g)}let a=t.message||"تعذّر رفع ملف ZIP إلى Vinstack";const v=t.failed??t.data?.failed??[];if(v.length){const g=v.slice(0,3).map(p=>`${p.name}: ${p.error}`).join(" — ");a=`${a} (${g}${v.length>3?` +${v.length-3}`:""})`}return a}async function Gn(e,t,a){const v=new FormData;v.append("stage",t),v.append("zip",a,a.name);try{const{data:g}=await Se.post(`/admin/vehicles/${e}/images/zip`,v);return g}catch(g){throw g.message=Nn(g),g}}function Ce(e){if(!e)return!1;const t=String(e.name||"").toLowerCase();return e.type==="application/zip"||e.type==="application/x-zip-compressed"||t.endsWith(".zip")}const Kn={class:"photos-panel"},Hn={key:0,class:"gallery-loading"},qn={key:1,class:"gallery-warning gallery-warning--danger"},Wn={key:2,class:"gallery-warning gallery-warning--danger"},Jn={key:3,class:"gallery-warning gallery-warning--ok"},Yn={key:0},Qn={key:1},Xn={key:0,class:"preview-block"},ea=["src","alt"],ta=["onDragenter","onDragover","onDragleave","onDrop"],na={class:"stage-card-header"},aa={class:"stage-card-title-wrap"},ia={class:"stage-title"},la={class:"stage-counts"},sa={class:"count-pill count-pill--vinstack",title:"صور من Vinstack"},ra={class:"count-pill count-pill--local",title:"صور مرفوعة من الإدارة"},oa={class:"stage-upload-row"},da=["onChange"],ua=["onChange"],ca={key:0,class:"zip-upload-progress"},pa={key:0,class:"stage-thumbs"},va=["onClick"],fa=["src","alt"],ma={key:0,class:"source-tag source-tag--local"},ha={key:1,class:"source-tag source-tag--vinstack"},ya={key:1,class:"stage-empty"},ga={key:0,class:"preview-block"},ba=["src","alt"],ka={key:1,class:"gallery-block"},wa={class:"gallery-main"},$a=["disabled"],xa=["src","alt"],Ca={key:1,class:"local-badge-inline"},La=["disabled"],Ia={key:0,class:"gallery-thumb-strip"},Sa=["onClick"],za=["src","alt"],Da={key:0,class:"local-dot",title:"مرفوعة من الإدارة"},Pa={class:"gallery-counter"},Ba={key:2,class:"no-hd"},_a={key:3,class:"no-photos"},Va={__name:"VehiclePhotosPanel",props:{vehicle:{type:Object,required:!0},compact:{type:Boolean,default:!1},adminMode:{type:Boolean,default:!1},apiMode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)}},emits:["updated"],setup(e,{emit:t}){const a=e,v=t,g=rt(),p=ot(),u=C(!1),D=C(null),A=C(null),$=C(null),m=C(null),P=C({}),S=C({}),V=C(null),z=C(0),j=C(!1),N=C(!1),x=C(null),Z=C(!1),G=C(!1),F=C(null),o=C(0),k={gallery_token_missing:"توكن المعرض غير مضبوط — أضف Gallery Token في الإعدادات أو استخدم توكن المزامنة.",gallery_token_expired:"توكن المعرض منتهي — حدّثه من الإعدادات."},w=b(()=>{const d=F.value;return d?k[d]??d:""}),B=b(()=>Rt(x.value??a.vehicle)),_=b(()=>Ut(x.value??a.vehicle)),f=b(()=>Tt(x.value??a.vehicle)),H=b(()=>Mt(x.value??a.vehicle)),fe=b(()=>Ot(x.value??a.vehicle)),J=b(()=>_.value[z.value]??null);async function Y(){const d=a.vehicle?.id,s=a.vehicle?.vin;if(!d||!s||!we(a.vehicle)){x.value=a.vehicle;return}N.value=!0,Z.value=!1,G.value=!1,F.value=null,o.value=0;try{const r=await Bt(d,a.apiMode);x.value=ce(a.vehicle,r),Z.value=!!r.gallery_fresh,G.value=!!r.gallery_token_expired,F.value=r.gallery_error??null,o.value=Number(r.gallery_new_images_count??0),v("updated",x.value)}catch(r){x.value=a.vehicle,F.value=r.response?.data?.message||"تعذّر الاتصال بـ API المعرض — تُعرض الصور المخزّنة."}finally{N.value=!1}}function Ve(d,s){x.value=ce(a.vehicle,d),v("updated",x.value)}dt(Y),ve(()=>[a.vehicle?.id,a.vehicle?.vin],()=>{Y()}),ve(_,d=>{z.value>=d.length&&(z.value=Math.max(0,d.length-1))});function Ae(d,s){s&&(P.value[d]=s)}function Ee(d,s){s&&(S.value[d]=s)}function le(d){return H.value[d]??[]}function me(d){const s=le(d),r=s.filter(c=>q(c)).length;return{total:s.length,uploaded:r,vinstack:s.length-r}}function q(d){return _t(d,fe.value)}function se(d){return Vt(d,fe.value)}function Re(d){P.value[d]?.click()}function Ue(d){S.value[d]?.click()}function Te(d){V.value=d}function Me(d){V.value=d}function Oe(d,s){s.currentTarget?.contains(s.relatedTarget)||V.value===d&&(V.value=null)}function Ze(d,s){V.value=null;const r=[...s.dataTransfer?.files??[]],c=r.find(oe=>Ce(oe));if(c){he(d,c);return}const Q=r.filter(oe=>oe.type.startsWith("image/"));if(!Q.length){g.add({severity:"warn",summary:"ملف غير مدعوم",detail:"اسحب صوراً أو ملف ZIP فقط",life:3500});return}ye(d,Q)}async function Fe(d,s){const r=s.target,c=[...r.files??[]];r.value="",c.length&&await ye(d,c)}async function je(d,s){const r=s.target,c=r.files?.[0];if(r.value="",!!c){if(!Ce(c)){g.add({severity:"warn",summary:"ملف غير مدعوم",detail:"يُقبل ملف ZIP فقط",life:3500});return}await he(d,c)}}async function he(d,s){if(!(!s||!a.vehicle?.id)){if(!we(a.vehicle)){g.add({severity:"warn",summary:"غير متاح",detail:"رفع ZIP إلى Vinstack متاح لسيارات المزامنة فقط",life:4e3});return}$.value=d;try{const r=await Gn(a.vehicle.id,d,s),c=r.data?.gallery;c?(x.value=ce(a.vehicle,c),Z.value=!!c.gallery_fresh,G.value=!!c.gallery_token_expired,F.value=c.gallery_error??null,o.value=Number(c.gallery_new_images_count??r.data?.uploaded??0),v("updated",x.value)):await Y(),g.add({severity:"success",summary:"تم رفع ZIP",detail:r.message||"تم رفع الصور إلى Vinstack وتحديث المعرض",life:4500})}catch(r){g.add({severity:"error",summary:"فشل رفع ZIP",detail:r.message||"تعذّر رفع ملف ZIP إلى Vinstack",life:5e3})}finally{$.value=null}}}async function ye(d,s){if(!(!s.length||!a.vehicle?.id)){A.value=d;try{const r=await At(a.vehicle.id,d,s),c=r.data?.vehicle??r.data;v("updated",c),await Y(),g.add({severity:"success",summary:"تم الرفع",detail:r.message||"تم رفع الصور بنجاح وتحديث المعرض",life:3500})}catch(r){g.add({severity:"error",summary:"فشل الرفع",detail:r.response?.data?.message||"تعذر رفع الصور",life:4e3})}finally{A.value=null}}}function Ne(d){!se(d)||!a.vehicle?.id||p.require({message:"هل أنت متأكد من حذف هذه الصورة؟ لن يتمكن التاجر من رؤيتها بعد الحذف.",header:"حذف الصورة",icon:"pi pi-exclamation-triangle",rejectLabel:"إلغاء",acceptLabel:"حذف",acceptClass:"p-button-danger",accept:()=>Ge(d)})}async function Ge(d){const s=se(d);if(!(!s||!a.vehicle?.id)){m.value=s;try{const r=await Et(a.vehicle.id,s);v("updated",r.data),await Y(),g.add({severity:r.cloudinary_warning?"warn":"success",summary:"تم الحذف",detail:r.message||r.cloudinary_warning||"تم حذف الصورة من المعرض",life:3e3})}catch(r){g.add({severity:"error",summary:"فشل الحذف",detail:r.response?.data?.message||"تعذر حذف الصورة",life:4e3})}finally{m.value=null}}}function ge(d){d&&(D.value=d,u.value=!0)}function re(d){j.value||d===z.value||(j.value=!0,z.value=d,window.setTimeout(()=>{j.value=!1},120))}function Ke(){z.value>0&&re(z.value-1)}function He(){z.value<_.value.length-1&&re(z.value+1)}return(d,s)=>(i(),l("div",Kn,[N.value?(i(),l("div",Hn,[L(I(ae),{style:{width:"28px",height:"28px"}}),s[3]||(s[3]=n("span",null,"جاري تحميل الصور المحدّثة...",-1))])):G.value?(i(),l("div",qn,[...s[4]||(s[4]=[n("i",{class:"pi pi-exclamation-triangle"},null,-1),n("span",null,"توكن API المعرض منتهي — راجع الإعدادات. تُعرض الصور المخزّنة إن وُجدت.",-1)])])):F.value?(i(),l("div",Wn,[s[5]||(s[5]=n("i",{class:"pi pi-exclamation-circle"},null,-1)),n("span",null,h(w.value),1)])):Z.value?(i(),l("div",Jn,[s[6]||(s[6]=n("i",{class:"pi pi-check-circle"},null,-1)),o.value>0?(i(),l("span",Yn," تم حفظ "+h(o.value)+" صورة جديدة من API المعرض ",1)):(i(),l("span",Qn,"صور محدّثة من API المعرض"))])):y("",!0),e.adminMode?(i(),l(R,{key:4},[s[13]||(s[13]=n("header",{class:"photos-section-header"},[n("h3",{class:"photos-section-title"},"إدارة الصور"),n("p",{class:"photos-section-sub"},"رفع صور جديدة للتاجر حسب المرحلة")],-1)),B.value?(i(),l("div",Xn,[n("img",{src:B.value,alt:f.value,class:"preview-img",loading:"lazy",decoding:"async"},null,8,ea),s[7]||(s[7]=n("span",{class:"preview-label"},"معاينة Vinstack",-1))])):y("",!0),(i(!0),l(R,null,K(I(Pt),r=>(i(),l("section",{key:r.key,class:T(["stage-card",{"stage-card--dragover":V.value===r.key}]),onDragenter:ee(c=>Te(r.key),["prevent"]),onDragover:ee(c=>Me(r.key),["prevent"]),onDragleave:c=>Oe(r.key,c),onDrop:ee(c=>Ze(r.key,c),["prevent"])},[n("div",na,[n("div",aa,[n("h4",ia,h(r.label),1),n("div",la,[n("span",sa,[s[8]||(s[8]=n("i",{class:"pi pi-cloud"},null,-1)),M(" Vinstack: "+h(me(r.key).vinstack),1)]),n("span",ra,[s[9]||(s[9]=n("i",{class:"pi pi-upload"},null,-1)),M(" مرفوعة من الإدارة: "+h(me(r.key).uploaded),1)])])])]),s[12]||(s[12]=n("div",{class:"stage-dropzone-hint"},[n("i",{class:"pi pi-cloud-upload"}),n("span",null,"اسحب الصور أو ملف ZIP هنا أو استخدم أزرار الرفع")],-1)),n("div",oa,[n("input",{ref_for:!0,ref:c=>Ae(r.key,c),type:"file",accept:"image/jpeg,image/png,image/webp,image/gif",multiple:"",class:"file-input",onChange:c=>Fe(r.key,c)},null,40,da),n("input",{ref_for:!0,ref:c=>Ee(r.key,c),type:"file",accept:".zip,application/zip,application/x-zip-compressed",class:"file-input",onChange:c=>je(r.key,c)},null,40,ua),L(I(O),{icon:"pi pi-upload",label:"رفع صور جديدة",loading:A.value===r.key,disabled:$.value===r.key,class:"upload-btn btn-add",onClick:c=>Re(r.key)},null,8,["loading","disabled","onClick"]),L(I(O),{icon:"pi pi-file-import",label:"رفع ملف مضغوط",severity:"secondary",loading:$.value===r.key,disabled:A.value===r.key,class:"upload-btn upload-btn--zip",onClick:c=>Ue(r.key)},null,8,["loading","disabled","onClick"]),$.value===r.key?(i(),l("span",ca,[...s[10]||(s[10]=[n("i",{class:"pi pi-spin pi-spinner"},null,-1),M(" جاري رفع ZIP إلى Vinstack… ",-1)])])):y("",!0)]),le(r.key).length?(i(),l("div",pa,[(i(!0),l(R,null,K(le(r.key),c=>(i(),l("div",{key:c,class:T(["thumb-card",{"thumb-card--uploaded":q(c)}])},[n("button",{type:"button",class:"thumb-btn",onClick:Q=>ge(c)},[n("img",{src:c,alt:f.value,loading:"lazy",decoding:"async"},null,8,fa)],8,va),q(c)?(i(),l("span",ma,"مرفوعة من الإدارة")):(i(),l("span",ha,"Vinstack")),q(c)?(i(),E(I(O),{key:2,icon:"pi pi-trash",severity:"danger",rounded:"",size:"small",class:"thumb-delete",loading:m.value===se(c),"aria-label":"حذف الصورة",onClick:Q=>Ne(c)},null,8,["loading","onClick"])):y("",!0)],2))),128))])):(i(),l("p",ya,[...s[11]||(s[11]=[n("i",{class:"pi pi-image"},null,-1),M(" لا توجد صور — ارفع صوراً للتاجر ",-1)])]))],42,ta))),128)),L(ke,{visible:u.value,"onUpdate:visible":s[0]||(s[0]=r=>u.value=r),vehicle:x.value??e.vehicle,"start-url":D.value,"api-mode":e.apiMode},null,8,["visible","vehicle","start-url","api-mode"])],64)):e.compact?(i(),E(ze,{key:5,vehicle:x.value??e.vehicle,"api-mode":e.apiMode,"show-button":"",onGalleryUpdated:Ve},null,8,["vehicle","api-mode"])):(i(),l(R,{key:6},[s[20]||(s[20]=n("header",{class:"photos-section-header photos-section-header--dealer"},[n("h3",{class:"photos-section-title"},"صور السيارة")],-1)),B.value?(i(),l("div",ga,[n("img",{src:B.value,alt:f.value,class:"preview-img",loading:"lazy",decoding:"async"},null,8,ba),s[14]||(s[14]=n("span",{class:"preview-label"},"معاينة",-1))])):y("",!0),_.value.length?(i(),l("div",ka,[n("div",wa,[n("button",{type:"button",class:"gallery-nav gallery-nav--prev",disabled:z.value===0,"aria-label":"الصورة السابقة",onClick:Ke},[...s[15]||(s[15]=[n("i",{class:"pi pi-chevron-right"},null,-1)])],8,$a),n("button",{type:"button",class:"main-photo",onClick:s[1]||(s[1]=r=>ge(J.value))},[J.value?(i(),l("img",{key:J.value,src:J.value,alt:f.value,decoding:"async"},null,8,xa)):y("",!0),q(J.value)?(i(),l("span",Ca,"مرفوعة من الإدارة")):y("",!0),s[16]||(s[16]=n("span",{class:"zoom-hint"},[n("i",{class:"pi pi-search-plus"}),M(" تكبير")],-1))]),n("button",{type:"button",class:"gallery-nav gallery-nav--next",disabled:z.value>=_.value.length-1,"aria-label":"الصورة التالية",onClick:He},[...s[17]||(s[17]=[n("i",{class:"pi pi-chevron-left"},null,-1)])],8,La)]),_.value.length>1?(i(),l("div",Ia,[(i(!0),l(R,null,K(_.value,(r,c)=>(i(),l("button",{key:`${c}-${r}`,type:"button",class:T(["gallery-thumb-btn",{active:c===z.value}]),onClick:Q=>re(c)},[n("img",{src:r,alt:`${f.value} thumbnail`,loading:"lazy",decoding:"async"},null,8,za),q(r)?(i(),l("span",Da)):y("",!0)],10,Sa))),128))])):y("",!0),n("p",Pa,h(z.value+1)+" / "+h(_.value.length),1)])):B.value?(i(),l("div",Ba,[...s[18]||(s[18]=[n("i",{class:"pi pi-info-circle"},null,-1),n("span",null,"لا توجد صور عالية الدقة — المعاينة فقط متاحة",-1)])])):(i(),l("div",_a,[...s[19]||(s[19]=[n("i",{class:"pi pi-image"},null,-1),n("span",null,"لا توجد صور لهذه السيارة",-1)])])),L(ke,{visible:u.value,"onUpdate:visible":s[2]||(s[2]=r=>u.value=r),vehicle:x.value,"start-url":D.value,"api-mode":e.apiMode},null,8,["visible","vehicle","start-url","api-mode"])],64))]))}},Aa=ie(Va,[["__scopeId","data-v-b225fc05"]]),Ea={key:0,class:"drawer-header"},Ra={class:"drawer-header-top"},Ua={class:"drawer-title"},Ta={class:"drawer-header-meta"},Ma={key:0,class:"stale-note"},Oa={key:1,class:"drawer-header drawer-header--loading"},Za={key:0,class:"drawer-loading"},Fa={key:1,class:"drawer-error"},ja={key:2,class:"drawer-body"},Na={class:"section-title"},Ga={class:"field-grid"},Ka={key:0,class:"detail-section"},Ha={class:"field-grid"},qa={key:1,class:"detail-section"},Wa={key:0,class:"record-list"},Ja={class:"record-title"},Ya={key:0,class:"record-sub"},Qa={key:1,class:"empty-section"},Xa={class:"detail-section"},ei={key:0,class:"record-list"},ti=["href"],ni={key:1,class:"empty-section"},ai={__name:"VehicleDetailDrawer",props:{visible:{type:Boolean,default:!1},vehicleId:{type:[Number,String],default:null},mode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)}},emits:["update:visible"],setup(e,{emit:t}){const a=e,v=t,g=C(!1),p=C(null),u=C(null),D=b({get:()=>a.visible,set:o=>v("update:visible",o)}),A=b(()=>Pe(u.value?.status)),$=b(()=>a.mode==="admin"),m=b(()=>{const o=u.value??{};return{id:o.id??a.vehicleId,vin:o.vin,year:o.year,make:o.make,model:o.model,images:o.images??[],images_by_stage:o.images_by_stage,uploaded_images:o.uploaded_images??[],raw_data:{thumbnail_url:o.thumbnail_url,images:o.images,images_by_stage:o.images_by_stage,uploaded_images:o.uploaded_images}}});function P(o){!o||!u.value||(u.value.images=o.images??u.value.images,u.value.images_by_stage=o.images_by_stage??u.value.images_by_stage,u.value.uploaded_images=o.uploaded_images??u.value.uploaded_images,u.value.thumbnail_url=o.thumbnail_url??u.value.thumbnail_url)}const S=b(()=>`${a.mode==="dealer"?"/dealer/vehicles":"/admin/vehicles"}/${a.vehicleId}/details`);ve(()=>[a.visible,a.vehicleId],([o,k])=>{o&&k&&V(),o||(u.value=null,p.value=null)});async function V(){if(a.vehicleId){g.value=!0,p.value=null;try{const{data:o}=await Se.get(S.value);u.value=o.data}catch(o){p.value=o.response?.data?.message||"Failed to load vehicle details.",u.value=null}finally{g.value=!1}}}function z(){D.value=!1}function j(){u.value=null,p.value=null}function N(o){return xe(o)||"—"}function x(o){if(o.value===null||o.value===void 0||o.value==="")return"—";if(o.type==="date")return xe(o.value)||"—";if(o.type==="money"){const k=Number(o.value);if(Number.isFinite(k))return new Intl.NumberFormat(void 0,{style:"currency",currency:"USD",maximumFractionDigits:0}).format(k)}return String(o.value)}function Z(o){return o.number||o.invoice_number||o.id||"Invoice"}function G(o){const k=[o.status,o.amount,o.date||o.created_at].filter(Boolean);return k.length?k.join(" · "):null}function F(o){return o.name||o.title||o.filename||o.type||"Document"}return(o,k)=>(i(),E(I(_e),{visible:D.value,"onUpdate:visible":k[0]||(k[0]=w=>D.value=w),position:"right",style:{width:"min(480px, 100vw)"},pt:{root:{class:"vehicle-detail-drawer"}},showCloseIcon:!1,onHide:j},{header:X(()=>[u.value?(i(),l("div",Ea,[n("div",Ra,[n("h2",Ua,h(u.value.title||"—"),1),L(I(O),{icon:"pi pi-times",text:"",rounded:"",severity:"secondary","aria-label":"Close",onClick:z})]),n("div",Ta,[u.value.status?(i(),l("span",{key:0,class:T(["status-pill",A.value])},[k[1]||(k[1]=n("span",{class:"status-dot"},null,-1)),M(" "+h(u.value.status),1)],2)):y("",!0),u.value.local_status?(i(),E(I(Be),{key:1,value:u.value.local_status,class:"local-tag"},null,8,["value"])):y("",!0)]),L(De,{vin:u.value.vin,class:"drawer-vin"},null,8,["vin"]),!u.value.vinstack_fresh&&!["manual","nujoom_al_jazeera"].includes(u.value.source)?(i(),l("div",Ma,[...k[2]||(k[2]=[n("i",{class:"pi pi-info-circle"},null,-1),M(" Showing cached data — Vinstack live fetch unavailable. ",-1)])])):y("",!0)])):(i(),l("div",Oa,[L(I($e),{width:"70%",height:"1.4rem"}),L(I($e),{width:"40%",height:"1rem",class:"mt-sm"})]))]),default:X(()=>[g.value?(i(),l("div",Za,[L(I(ae),{style:{width:"36px",height:"36px"}})])):p.value?(i(),l("div",Fa,[k[3]||(k[3]=n("i",{class:"pi pi-exclamation-circle"},null,-1)),n("span",null,h(p.value),1),L(I(O),{label:"Retry",size:"small",outlined:"",onClick:V})])):u.value?(i(),l("div",ja,[L(Aa,{vehicle:m.value,"admin-mode":$.value,"api-mode":e.mode,onUpdated:P},null,8,["vehicle","admin-mode","api-mode"]),(i(!0),l(R,null,K(u.value.sections,w=>(i(),l("section",{key:w.key,class:"detail-section"},[n("h3",Na,h(w.title),1),n("dl",Ga,[(i(!0),l(R,null,K(w.fields,B=>(i(),l(R,{key:`${w.key}-${B.key}`},[n("dt",null,h(B.label),1),n("dd",null,h(x(B)),1)],64))),128))])]))),128)),u.value.assignment?.dealer_name?(i(),l("section",Ka,[k[6]||(k[6]=n("h3",{class:"section-title"},"Assignment",-1)),n("dl",Ha,[k[4]||(k[4]=n("dt",null,"Dealer",-1)),n("dd",null,h(u.value.assignment.dealer_name),1),k[5]||(k[5]=n("dt",null,"Assigned",-1)),n("dd",null,h(N(u.value.assignment.assigned_at)),1)])])):y("",!0),e.mode!=="dealer"?(i(),l("section",qa,[k[7]||(k[7]=n("h3",{class:"section-title"},"Invoices",-1)),u.value.invoices?.length?(i(),l("div",Wa,[(i(!0),l(R,null,K(u.value.invoices,(w,B)=>(i(),l("div",{key:w.id??B,class:"record-item"},[n("div",Ja,h(Z(w)),1),G(w)?(i(),l("div",Ya,h(G(w)),1)):y("",!0)]))),128))])):(i(),l("p",Qa,"—"))])):y("",!0),n("section",Xa,[k[9]||(k[9]=n("h3",{class:"section-title"},"Documents",-1)),u.value.documents?.length?(i(),l("div",ei,[(i(!0),l(R,null,K(u.value.documents,(w,B)=>(i(),l("a",{key:w.id??w.url??B,href:w.url||w.link||"#",class:"record-item record-link",target:"_blank",rel:"noopener noreferrer"},[k[8]||(k[8]=n("i",{class:"pi pi-file"},null,-1)),n("span",null,h(F(w)),1)],8,ti))),128))])):(i(),l("p",ni,"—"))])])):y("",!0)]),_:1},8,["visible"]))}},ci=ie(ai,[["__scopeId","data-v-b0ec30eb"]]);export{ui as V,ci as a,_e as b,Aa as c,ln as s};
