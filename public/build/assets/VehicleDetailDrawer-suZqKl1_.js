import{a as qe}from"./index-DfcrmImM.js";import{B as Le,I as We,J as Je,s as Z,ag as Ye,aa as Qe,K as Ie,ah as Xe,ai as et,ab as tt,Z as ue,aj as nt,m as be,a0 as at,o as i,c as V,w as ee,k as l,x as T,d as I,a2 as it,h as lt,q as J,F as R,f as n,n as M,t as h,l as f,a1 as st,i as O,g as te,e as L,E as b,p as H,ak as ce,r as C,a6 as Se,a as rt,D as ot,j as dt,y as fe}from"./app-C_ZAimx9.js";import{V as ze,e as Pe,v as ut,f as ct,g as pt,h as vt,i as ft,j as mt,k as ht,l as yt,m as gt,n as bt,o as kt,p as De,q as wt,r as $t,t as xt,u as Ct,w as Lt,x as It,y as St,z as zt,A as Pt,B as Dt,C as Bt,D as _t,G as Vt,_ as ke,E as we,F as At,H as pe,I as Et,J as Rt,K as Ut,L as Tt,M as Mt,N as Ot,O as Zt,P as Ft,Q as jt,R as $e,S as xe}from"./useInfiniteScroll-DGtyPvFF.js";import{s as ie}from"./index-DVK0Plj5.js";import{s as Be}from"./index-By774UCa.js";import{_ as le}from"./_plugin-vue_export-helper-DlAUqK2U.js";var Nt=`
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
`,Gt={mask:function(t){var a=t.position,v=t.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:a==="left"?"flex-start":a==="right"?"flex-end":"center",alignItems:a==="top"?"flex-start":a==="bottom"?"flex-end":"center",pointerEvents:v?"auto":"none"}},root:{pointerEvents:"auto"}},Kt={mask:function(t){var a=t.instance,v=t.props,y=["left","right","top","bottom"],p=y.find(function(u){return u===v.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":v.modal,"p-drawer-open":a.containerVisible,"p-drawer-full":a.fullScreen},p?"p-drawer-".concat(p):""]},root:function(t){var a=t.instance;return["p-drawer p-component",{"p-drawer-full":a.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},Ht=Le.extend({name:"drawer",style:Nt,classes:Kt,inlineStyles:Gt}),qt={name:"BaseDrawer",extends:Qe,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:Ht,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function ne(e){"@babel/helpers - typeof";return ne=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ne(e)}function ve(e,t,a){return(t=Wt(t))in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function Wt(e){var t=Jt(e,"string");return ne(t)=="symbol"?t:t+""}function Jt(e,t){if(ne(e)!="object"||!e)return e;var a=e[Symbol.toPrimitive];if(a!==void 0){var v=a.call(e,t);if(ne(v)!="object")return v;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var _e={name:"Drawer",extends:qt,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(t){t&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&ue.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&ue.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&nt(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&ue.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(t){this.dismissable&&this.modal&&this.mask===t.target&&this.hide()},focus:function(){var t=function(y){return y&&y.querySelector("[autofocus]")},a=this.$slots.header&&t(this.headerContainer);a||(a=this.$slots.default&&t(this.container),a||(a=this.$slots.footer&&t(this.footerContainer),a||(a=this.closeButton))),a&&tt(a)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&et()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&Xe()},onKeydown:function(t){t.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(t){this.container=t},maskRef:function(t){this.mask=t},contentRef:function(t){this.content=t},headerContainerRef:function(t){this.headerContainer=t},footerContainerRef:function(t){this.footerContainer=t},closeButtonRef:function(t){this.closeButton=t?t.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var t=this;this.outsideClickListener||(this.outsideClickListener=function(a){t.isOutsideClicked(a)&&t.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(t){return this.container&&!this.container.contains(t.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return Ie(ve(ve(ve({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:Ye},components:{Button:Z,Portal:Je,TimesIcon:We}},Yt=["data-p"],Qt=["role","aria-modal","data-p"];function Xt(e,t,a,v,y,p){var u=be("Button"),D=be("Portal"),B=at("focustrap");return i(),V(D,null,{default:ee(function(){return[y.containerVisible?(i(),l("div",T({key:0,ref:p.maskRef,onMousedown:t[0]||(t[0]=function(){return p.onMaskClick&&p.onMaskClick.apply(p,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":p.dataP},e.ptm("mask")),[I(it,T({name:"p-drawer",onEnter:p.onEnter,onAfterEnter:p.onAfterEnter,onBeforeLeave:p.onBeforeLeave,onLeave:p.onLeave,onAfterLeave:p.onAfterLeave,appear:""},e.ptm("transition")),{default:ee(function(){return[e.visible?lt((i(),l("div",T({key:0,ref:p.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":p.dataP},e.ptmi("root")),[e.$slots.container?J(e.$slots,"container",{key:0,closeCallback:p.hide}):(i(),l(R,{key:1},[n("div",T({ref:p.headerContainerRef,class:e.cx("header")},e.ptm("header")),[J(e.$slots,"header",{class:M(e.cx("title"))},function(){return[e.header?(i(),l("div",T({key:0,class:e.cx("title")},e.ptm("title")),h(e.header),17)):f("",!0)]}),e.showCloseIcon?J(e.$slots,"closebutton",{key:0,closeCallback:p.hide},function(){return[I(u,T({ref:p.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":p.closeAriaLabel,unstyled:e.unstyled,onClick:p.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:ee(function($){return[J(e.$slots,"closeicon",{},function(){return[(i(),V(st(e.closeIcon?"span":"TimesIcon"),T({class:[e.closeIcon,$.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):f("",!0)],16),n("div",T({ref:p.contentRef,class:e.cx("content")},e.ptm("content")),[J(e.$slots,"default")],16),e.$slots.footer?(i(),l("div",T({key:0,ref:p.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[J(e.$slots,"footer")],16)):f("",!0)],64))],16,Qt)),[[B]]):f("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,Yt)):f("",!0)]}),_:3})}_e.render=Xt;var en=`
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
`,tn={root:function(t){var a=t.instance,v=t.props;return["p-textarea p-component",{"p-filled":a.$filled,"p-textarea-resizable ":v.autoResize,"p-textarea-sm p-inputfield-sm":v.size==="small","p-textarea-lg p-inputfield-lg":v.size==="large","p-invalid":a.$invalid,"p-variant-filled":a.$variant==="filled","p-textarea-fluid":a.$fluid}]}},nn=Le.extend({name:"textarea",style:en,classes:tn}),an={name:"BaseTextarea",extends:qe,props:{autoResize:Boolean},style:nn,provide:function(){return{$pcTextarea:this,$parentInstance:this}}};function ae(e){"@babel/helpers - typeof";return ae=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ae(e)}function ln(e,t,a){return(t=sn(t))in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function sn(e){var t=rn(e,"string");return ae(t)=="symbol"?t:t+""}function rn(e,t){if(ae(e)!="object"||!e)return e;var a=e[Symbol.toPrimitive];if(a!==void 0){var v=a.call(e,t);if(ae(v)!="object")return v;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var on={name:"Textarea",extends:an,inheritAttrs:!1,observer:null,mounted:function(){var t=this;this.autoResize&&(this.observer=new ResizeObserver(function(){requestAnimationFrame(function(){t.resize()})}),this.observer.observe(this.$el))},updated:function(){this.autoResize&&this.resize()},beforeUnmount:function(){this.observer&&this.observer.disconnect()},methods:{resize:function(){if(this.$el.offsetParent){var t=this.$el.style.height,a=parseInt(t)||0,v=this.$el.scrollHeight,y=!a||v>a,p=a&&v<a;p?(this.$el.style.height="auto",this.$el.style.height="".concat(this.$el.scrollHeight,"px")):y&&(this.$el.style.height="".concat(v,"px"))}},onInput:function(t){this.autoResize&&this.resize(),this.writeValue(t.target.value,t)}},computed:{attrs:function(){return T(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return Ie(ln({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},dn=["value","name","disabled","aria-invalid","data-p"];function un(e,t,a,v,y,p){return i(),l("textarea",T({class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.invalid||void 0,"data-p":p.dataP,onInput:t[0]||(t[0]=function(){return p.onInput&&p.onInput.apply(p,arguments)})},p.attrs),null,16,dn)}on.render=un;const cn={class:"vehicle-row"},pn={class:"cell cell-vehicle"},vn={class:"vehicle-info"},fn={class:"title-line"},mn={key:1,class:"pi pi-images local-upload-hint",title:"يوجد صور مرفوعة محلياً للتاجر","aria-label":"صور مرفوعة محلياً"},hn={class:"entered-by"},yn={class:"cell cell-lot"},gn={class:"lot-id"},bn={class:"auction"},kn={class:"cell cell-route"},wn={key:0,class:"route-line"},$n={key:1,class:"route-line"},xn={class:"cell cell-refs"},Cn={key:0,class:"ref-line ref-line--container"},Ln={class:"ref-container-num"},In={key:1,class:"ref-line"},Sn={class:"ref-badges"},zn={class:"mini-badge mini-badge--neutral"},Pn={class:"cell cell-dates"},Dn={class:"date-row"},Bn={class:"date-value"},_n={class:"date-row"},Vn={class:"date-value"},An={key:0,class:"cell cell-admin"},En={key:1,class:"dealer-tag"},Rn={class:"dealer-tag__name"},Un={key:1,class:"cell cell-actions"},Tn={__name:"VehicleListRow",props:{vehicle:{type:Object,required:!0},mode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)},trackingAvailable:{type:Boolean,default:!1}},emits:["assign","update-status","open-detail","edit","unassign","track-container"],setup(e){const t=e,a=b(()=>t.vehicle?.source==="manual"),v=b(()=>ut(t.vehicle)),y=b(()=>ct(t.vehicle)),p=b(()=>pt(t.vehicle)),u=b(()=>vt(t.vehicle)),D=b(()=>(t.vehicle?.uploaded_images?.length??0)>0),B=b(()=>ft(t.vehicle)),$=b(()=>mt(B.value)),m=b(()=>ht(t.vehicle)),_=b(()=>yt(t.vehicle)),z=b(()=>gt(t.vehicle)),A=b(()=>bt(t.vehicle)),S=b(()=>kt(t.vehicle)),q=b(()=>De(S.value)),N=b(()=>wt(t.vehicle)),w=b(()=>N.value?t.trackingAvailable:!1),G=b(()=>$t(t.vehicle)),U=b(()=>xt(t.vehicle)),F=b(()=>Ct(t.vehicle)),o=b(()=>Lt(t.vehicle)),k=b(()=>It(t.vehicle)),x=b(()=>St(t.vehicle)),P=b(()=>t.vehicle.active_assignment?.dealer?.company_name??null),j=b(()=>zt(t.vehicle));return(E,g)=>(i(),l("div",cn,[n("div",pn,[I(ze,{vehicle:e.vehicle,variant:"row"},null,8,["vehicle"]),n("div",vn,[n("div",fn,[n("button",{type:"button",class:"title title-link",onClick:g[0]||(g[0]=K=>E.$emit("open-detail",e.vehicle))},h(u.value||"—"),1),B.value?(i(),l("span",{key:0,class:M(["fuel-badge",$.value])},h(B.value),3)):f("",!0),n("span",{class:M(["source-pill",p.value])},h(y.value),3),e.mode==="admin"&&D.value?(i(),l("i",mn)):f("",!0)]),I(Pe,{vin:e.vehicle.vin,class:"vehicle-vin-line"},null,8,["vin"]),n("div",hn,"Entered by "+h(x.value),1)])]),n("div",yn,[n("div",gn,h(m.value||"—"),1),n("div",bn,h(_.value||"—"),1)]),n("div",kn,[z.value?(i(),l("div",wn,[g[6]||(g[6]=n("span",{class:"route-dot route-dot--origin"},null,-1)),n("span",null,h(z.value),1)])):f("",!0),A.value?(i(),l("div",$n,[g[7]||(g[7]=n("i",{class:"pi pi-map-marker route-pin"},null,-1)),n("span",null,h(A.value),1)])):f("",!0),S.value?(i(),l("span",{key:2,class:M(["status-pill",q.value])},[g[8]||(g[8]=n("span",{class:"status-dot"},null,-1)),O(" "+h(S.value),1)],2)):f("",!0)]),n("div",xn,[N.value?(i(),l("div",Cn,[g[9]||(g[9]=n("i",{class:"pi pi-box ref-icon"},null,-1)),n("span",Ln,h(N.value),1),I(L(Z),{icon:"pi pi-map-marker",severity:w.value?"info":"secondary",text:"",rounded:"",size:"small",disabled:!w.value,class:M(["track-btn",{"track-btn--ready":w.value}]),"aria-label":"تتبع الحاوية",title:"تتبع الحاوية",onClick:g[1]||(g[1]=te(K=>E.$emit("track-container",e.vehicle),["stop"]))},null,8,["severity","disabled","class"])])):f("",!0),G.value?(i(),l("div",In,[g[10]||(g[10]=n("i",{class:"pi pi-file ref-icon"},null,-1)),n("span",null,h(G.value),1)])):f("",!0),n("div",Sn,[U.value.label?(i(),l("span",{key:0,class:M(["mini-badge",U.value.present?"mini-badge--ok":"mini-badge--bad"])},[g[11]||(g[11]=n("i",{class:"pi pi-key"},null,-1)),O(" "+h(U.value.label),1)],2)):f("",!0),n("span",zn,[g[12]||(g[12]=n("i",{class:"pi pi-file"},null,-1)),O(" "+h(F.value),1)])])]),n("div",Pn,[n("div",Dn,[g[13]||(g[13]=n("span",{class:"date-label"},"Purchase",-1)),n("span",Bn,h(o.value||"—"),1)]),n("div",_n,[g[14]||(g[14]=n("span",{class:"date-label"},"Arrived terminal",-1)),n("span",Vn,h(k.value||"—"),1)])]),e.mode==="admin"?(i(),l("div",An,[e.vehicle.status?(i(),l("span",{key:0,class:M(["status-pill assignment-pill",j.value])},[g[15]||(g[15]=n("span",{class:"status-dot"},null,-1)),O(" "+h(e.vehicle.status),1)],2)):f("",!0),P.value?(i(),l("div",En,[n("span",Rn,h(P.value),1),n("button",{type:"button",class:"dealer-tag__remove",title:"إلغاء الإسناد","aria-label":"إلغاء إسناد التاجر",onClick:g[2]||(g[2]=te(K=>E.$emit("unassign",e.vehicle),["stop"]))},[...g[16]||(g[16]=[n("i",{class:"pi pi-times"},null,-1)])])])):f("",!0),a.value?(i(),V(L(Z),{key:2,icon:"pi pi-pencil",label:"تعديل",size:"small",severity:"secondary",outlined:"",title:"تعديل سيارة يدوية",onClick:g[3]||(g[3]=K=>E.$emit("edit",e.vehicle))})):f("",!0),v.value?f("",!0):(i(),V(L(Z),{key:3,label:"إسناد",size:"small",class:"btn-assign",onClick:g[4]||(g[4]=K=>E.$emit("assign",e.vehicle))}))])):(i(),l("div",Un,[I(L(Be),{value:e.vehicle.status,class:"local-tag"},null,8,["value"]),I(L(Z),{icon:"pi pi-pencil",severity:"secondary",text:"",rounded:"",title:"تحديث الحالة",onClick:g[5]||(g[5]=K=>E.$emit("update-status",e.vehicle))})]))]))}},Mn=le(Tn,[["__scopeId","data-v-f8b5a48a"]]),On={class:"vehicle-list-panel"},Zn={key:0,class:"list-header"},Fn={key:1,class:"list-loading"},jn={key:2,class:"list-empty"},Nn={key:0,class:"empty-hint"},Gn={key:3,class:"list-body"},Kn={__name:"VehicleListPanel",props:{vehicles:{type:Array,default:()=>[]},loading:{type:Boolean,default:!1},loadingMore:{type:Boolean,default:!1},total:{type:Number,default:0},page:{type:Number,default:1},perPage:{type:Number,default:50},mode:{type:String,default:"admin"},showHeader:{type:Boolean,default:!0},emptyText:{type:String,default:"لا توجد سيارات مسندة إليك"},emptyHint:{type:String,default:""},emptyActionLabel:{type:String,default:""},infiniteScroll:{type:Boolean,default:!1},hasMore:{type:Boolean,default:!1},trackingAvailable:{type:Boolean,default:!1}},emits:["assign","unassign","update-status","open-detail","edit","page","empty-action","load-more"],setup(e,{emit:t}){const a=e,v=t,{sentinel:y}=Pt({enabled:ce(a,"infiniteScroll"),hasMore:ce(a,"hasMore"),loading:ce(a,"loadingMore"),onLoadMore:()=>v("load-more")}),p=y,u=C(!1),D=C(null);function B($){const m=_t($);m&&(D.value=m,u.value=!0)}return($,m)=>(i(),l("div",On,[e.showHeader?(i(),l("div",Zn,[m[8]||(m[8]=n("span",null,"Vehicle",-1)),m[9]||(m[9]=n("span",null,"ID & source",-1)),m[10]||(m[10]=n("span",null,"Route & status",-1)),m[11]||(m[11]=n("span",null,"References",-1)),m[12]||(m[12]=n("span",null,"Dates",-1)),n("span",null,h(e.mode==="admin"?"Assignment":"Actions"),1)])):f("",!0),e.loading&&!e.vehicles.length?(i(),l("div",Fn,[I(L(ie),{style:{width:"36px",height:"36px"}})])):e.vehicles.length?(i(),l("div",Gn,[(i(!0),l(R,null,H(e.vehicles,_=>(i(),V(Mn,{key:_.id,vehicle:_,mode:e.mode,"tracking-available":e.trackingAvailable,onAssign:m[1]||(m[1]=z=>$.$emit("assign",z)),onUnassign:m[2]||(m[2]=z=>$.$emit("unassign",z)),onUpdateStatus:m[3]||(m[3]=z=>$.$emit("update-status",z)),onOpenDetail:m[4]||(m[4]=z=>$.$emit("open-detail",z)),onEdit:m[5]||(m[5]=z=>$.$emit("edit",z)),onTrackContainer:B},null,8,["vehicle","mode","tracking-available"]))),128)),e.infiniteScroll&&e.hasMore?(i(),l("div",{key:0,ref_key:"sentinelRef",ref:p,class:"list-sentinel","aria-hidden":"true"},[e.loadingMore?(i(),V(L(ie),{key:0,style:{width:"28px",height:"28px"}})):f("",!0)],512)):f("",!0)])):(i(),l("div",jn,[m[13]||(m[13]=n("i",{class:"pi pi-car"},null,-1)),n("span",null,h(e.emptyText),1),e.emptyHint?(i(),l("p",Nn,h(e.emptyHint),1)):f("",!0),e.emptyActionLabel?(i(),V(L(Z),{key:1,label:e.emptyActionLabel,icon:"pi pi-refresh",outlined:"",size:"small",onClick:m[0]||(m[0]=_=>$.$emit("empty-action"))},null,8,["label"])):f("",!0)])),!e.infiniteScroll&&e.total>e.perPage?(i(),V(L(Dt),{key:4,rows:e.perPage,"total-records":e.total,first:(e.page-1)*e.perPage,template:"FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink",class:"list-paginator",onPage:m[6]||(m[6]=_=>$.$emit("page",_))},null,8,["rows","total-records","first"])):f("",!0),e.trackingAvailable?(i(),V(Bt,{key:5,visible:u.value,"onUpdate:visible":m[7]||(m[7]=_=>u.value=_),"api-role":e.mode,container:D.value},null,8,["visible","api-role","container"])):f("",!0)]))}},vi=le(Kn,[["__scopeId","data-v-592b36b5"]]);function Hn(e){const t=e?.response?.data;if(!t)return e?.message||"تعذّر رفع ملف ZIP إلى Vinstack";if(t.errors&&typeof t.errors=="object"){const y=Object.values(t.errors).flat()[0];if(y)return String(y)}let a=t.message||"تعذّر رفع ملف ZIP إلى Vinstack";const v=t.failed??t.data?.failed??[];if(v.length){const y=v.slice(0,3).map(p=>`${p.name}: ${p.error}`).join(" — ");a=`${a} (${y}${v.length>3?` +${v.length-3}`:""})`}return a}async function qn(e,t,a){const v=new FormData;v.append("stage",t),v.append("zip",a,a.name);try{const{data:y}=await Se.post(`/admin/vehicles/${e}/images/zip`,v);return y}catch(y){throw y.message=Hn(y),y}}function Ce(e){if(!e)return!1;const t=String(e.name||"").toLowerCase();return e.type==="application/zip"||e.type==="application/x-zip-compressed"||t.endsWith(".zip")}const Wn={class:"photos-panel"},Jn={key:0,class:"gallery-loading"},Yn={key:1,class:"gallery-warning gallery-warning--danger"},Qn={key:2,class:"gallery-warning gallery-warning--danger"},Xn={key:3,class:"gallery-warning gallery-warning--ok"},ea={key:0},ta={key:1},na={key:0,class:"preview-block"},aa=["src","alt"],ia=["onDragenter","onDragover","onDragleave","onDrop"],la={class:"stage-card-header"},sa={class:"stage-card-title-wrap"},ra={class:"stage-title"},oa={class:"stage-counts"},da={class:"count-pill count-pill--vinstack",title:"صور من Vinstack"},ua={class:"count-pill count-pill--local",title:"صور مرفوعة من الإدارة"},ca={class:"stage-upload-row"},pa=["onChange"],va=["onChange"],fa={key:0,class:"zip-upload-progress"},ma={key:0,class:"stage-thumbs"},ha=["onClick"],ya=["src","alt"],ga={key:0,class:"source-tag source-tag--local"},ba={key:1,class:"source-tag source-tag--vinstack"},ka={key:1,class:"stage-empty"},wa={key:0,class:"preview-block"},$a=["src","alt"],xa={key:1,class:"gallery-block"},Ca={class:"gallery-main"},La=["disabled"],Ia=["src","alt"],Sa={key:1,class:"local-badge-inline"},za=["disabled"],Pa={key:0,class:"gallery-thumb-strip"},Da=["onClick"],Ba=["src","alt"],_a={key:0,class:"local-dot",title:"مرفوعة من الإدارة"},Va={class:"gallery-counter"},Aa={key:2,class:"no-hd"},Ea={key:3,class:"no-photos"},Ra={__name:"VehiclePhotosPanel",props:{vehicle:{type:Object,required:!0},compact:{type:Boolean,default:!1},adminMode:{type:Boolean,default:!1},apiMode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)}},emits:["updated"],setup(e,{emit:t}){const a=e,v=t,y=rt(),p=ot(),u=C(!1),D=C(null),B=C(null),$=C(null),m=C(null),_=C({}),z=C({}),A=C(null),S=C(0),q=C(!1),N=C(!1),w=C(null),G=C(!1),U=C(!1),F=C(null),o=C(0),k={gallery_token_missing:"توكن المعرض غير مضبوط — أضف Gallery Token في الإعدادات أو استخدم توكن المزامنة.",gallery_token_expired:"توكن المعرض منتهي — حدّثه من الإعدادات."},x=b(()=>{const d=F.value;return d?k[d]??d:""}),P=b(()=>Mt(w.value??a.vehicle)),j=b(()=>Ot(w.value??a.vehicle)),E=b(()=>Zt(w.value??a.vehicle)),g=b(()=>Ft(w.value??a.vehicle)),K=b(()=>jt(w.value??a.vehicle)),Y=b(()=>j.value[S.value]??null);async function Q(){const d=a.vehicle?.id,s=a.vehicle?.vin;if(!d||!s||!we(a.vehicle)){w.value=a.vehicle;return}N.value=!0,G.value=!1,U.value=!1,F.value=null,o.value=0;try{const r=await At(d,a.apiMode);w.value=pe(a.vehicle,r),G.value=!!r.gallery_fresh,U.value=!!r.gallery_token_expired,F.value=r.gallery_error??null,o.value=Number(r.gallery_new_images_count??0),v("updated",w.value)}catch(r){w.value=a.vehicle,F.value=r.response?.data?.message||"تعذّر الاتصال بـ API المعرض — تُعرض الصور المخزّنة."}finally{N.value=!1}}function Ve(d,s){w.value=pe(a.vehicle,d),v("updated",w.value)}dt(Q),fe(()=>[a.vehicle?.id,a.vehicle?.vin],()=>{Q()}),fe(j,d=>{S.value>=d.length&&(S.value=Math.max(0,d.length-1))});function Ae(d,s){s&&(_.value[d]=s)}function Ee(d,s){s&&(z.value[d]=s)}function se(d){return g.value[d]??[]}function me(d){const s=se(d),r=s.filter(c=>W(c)).length;return{total:s.length,uploaded:r,vinstack:s.length-r}}function W(d){return Et(d,K.value)}function re(d){return Rt(d,K.value)}function Re(d){_.value[d]?.click()}function Ue(d){z.value[d]?.click()}function Te(d){A.value=d}function Me(d){A.value=d}function Oe(d,s){s.currentTarget?.contains(s.relatedTarget)||A.value===d&&(A.value=null)}function Ze(d,s){A.value=null;const r=[...s.dataTransfer?.files??[]],c=r.find(de=>Ce(de));if(c){he(d,c);return}const X=r.filter(de=>de.type.startsWith("image/"));if(!X.length){y.add({severity:"warn",summary:"ملف غير مدعوم",detail:"اسحب صوراً أو ملف ZIP فقط",life:3500});return}ye(d,X)}async function Fe(d,s){const r=s.target,c=[...r.files??[]];r.value="",c.length&&await ye(d,c)}async function je(d,s){const r=s.target,c=r.files?.[0];if(r.value="",!!c){if(!Ce(c)){y.add({severity:"warn",summary:"ملف غير مدعوم",detail:"يُقبل ملف ZIP فقط",life:3500});return}await he(d,c)}}async function he(d,s){if(!(!s||!a.vehicle?.id)){if(!we(a.vehicle)){y.add({severity:"warn",summary:"غير متاح",detail:"رفع ZIP إلى Vinstack متاح لسيارات المزامنة فقط",life:4e3});return}$.value=d;try{const r=await qn(a.vehicle.id,d,s),c=r.data?.gallery;c?(w.value=pe(a.vehicle,c),G.value=!!c.gallery_fresh,U.value=!!c.gallery_token_expired,F.value=c.gallery_error??null,o.value=Number(c.gallery_new_images_count??r.data?.uploaded??0),v("updated",w.value)):await Q(),y.add({severity:"success",summary:"تم رفع ZIP",detail:r.message||"تم رفع الصور إلى Vinstack وتحديث المعرض",life:4500})}catch(r){y.add({severity:"error",summary:"فشل رفع ZIP",detail:r.message||"تعذّر رفع ملف ZIP إلى Vinstack",life:5e3})}finally{$.value=null}}}async function ye(d,s){if(!(!s.length||!a.vehicle?.id)){B.value=d;try{const r=await Ut(a.vehicle.id,d,s),c=r.data?.vehicle??r.data;v("updated",c),await Q(),y.add({severity:"success",summary:"تم الرفع",detail:r.message||"تم رفع الصور بنجاح وتحديث المعرض",life:3500})}catch(r){y.add({severity:"error",summary:"فشل الرفع",detail:r.response?.data?.message||"تعذر رفع الصور",life:4e3})}finally{B.value=null}}}function Ne(d){!re(d)||!a.vehicle?.id||p.require({message:"هل أنت متأكد من حذف هذه الصورة؟ لن يتمكن التاجر من رؤيتها بعد الحذف.",header:"حذف الصورة",icon:"pi pi-exclamation-triangle",rejectLabel:"إلغاء",acceptLabel:"حذف",acceptClass:"p-button-danger",accept:()=>Ge(d)})}async function Ge(d){const s=re(d);if(!(!s||!a.vehicle?.id)){m.value=s;try{const r=await Tt(a.vehicle.id,s);v("updated",r.data),await Q(),y.add({severity:r.cloudinary_warning?"warn":"success",summary:"تم الحذف",detail:r.message||r.cloudinary_warning||"تم حذف الصورة من المعرض",life:3e3})}catch(r){y.add({severity:"error",summary:"فشل الحذف",detail:r.response?.data?.message||"تعذر حذف الصورة",life:4e3})}finally{m.value=null}}}function ge(d){d&&(D.value=d,u.value=!0)}function oe(d){q.value||d===S.value||(q.value=!0,S.value=d,window.setTimeout(()=>{q.value=!1},120))}function Ke(){S.value>0&&oe(S.value-1)}function He(){S.value<j.value.length-1&&oe(S.value+1)}return(d,s)=>(i(),l("div",Wn,[N.value?(i(),l("div",Jn,[I(L(ie),{style:{width:"28px",height:"28px"}}),s[3]||(s[3]=n("span",null,"جاري تحميل الصور المحدّثة...",-1))])):U.value?(i(),l("div",Yn,[...s[4]||(s[4]=[n("i",{class:"pi pi-exclamation-triangle"},null,-1),n("span",null,"توكن API المعرض منتهي — راجع الإعدادات. تُعرض الصور المخزّنة إن وُجدت.",-1)])])):F.value?(i(),l("div",Qn,[s[5]||(s[5]=n("i",{class:"pi pi-exclamation-circle"},null,-1)),n("span",null,h(x.value),1)])):G.value?(i(),l("div",Xn,[s[6]||(s[6]=n("i",{class:"pi pi-check-circle"},null,-1)),o.value>0?(i(),l("span",ea," تم حفظ "+h(o.value)+" صورة جديدة من API المعرض ",1)):(i(),l("span",ta,"صور محدّثة من API المعرض"))])):f("",!0),e.adminMode?(i(),l(R,{key:4},[s[13]||(s[13]=n("header",{class:"photos-section-header"},[n("h3",{class:"photos-section-title"},"إدارة الصور"),n("p",{class:"photos-section-sub"},"رفع صور جديدة للتاجر حسب المرحلة")],-1)),P.value?(i(),l("div",na,[n("img",{src:P.value,alt:E.value,class:"preview-img",loading:"lazy",decoding:"async"},null,8,aa),s[7]||(s[7]=n("span",{class:"preview-label"},"معاينة Vinstack",-1))])):f("",!0),(i(!0),l(R,null,H(L(Vt),r=>(i(),l("section",{key:r.key,class:M(["stage-card",{"stage-card--dragover":A.value===r.key}]),onDragenter:te(c=>Te(r.key),["prevent"]),onDragover:te(c=>Me(r.key),["prevent"]),onDragleave:c=>Oe(r.key,c),onDrop:te(c=>Ze(r.key,c),["prevent"])},[n("div",la,[n("div",sa,[n("h4",ra,h(r.label),1),n("div",oa,[n("span",da,[s[8]||(s[8]=n("i",{class:"pi pi-cloud"},null,-1)),O(" Vinstack: "+h(me(r.key).vinstack),1)]),n("span",ua,[s[9]||(s[9]=n("i",{class:"pi pi-upload"},null,-1)),O(" مرفوعة من الإدارة: "+h(me(r.key).uploaded),1)])])])]),s[12]||(s[12]=n("div",{class:"stage-dropzone-hint"},[n("i",{class:"pi pi-cloud-upload"}),n("span",null,"اسحب الصور أو ملف ZIP هنا أو استخدم أزرار الرفع")],-1)),n("div",ca,[n("input",{ref_for:!0,ref:c=>Ae(r.key,c),type:"file",accept:"image/jpeg,image/png,image/webp,image/gif",multiple:"",class:"file-input",onChange:c=>Fe(r.key,c)},null,40,pa),n("input",{ref_for:!0,ref:c=>Ee(r.key,c),type:"file",accept:".zip,application/zip,application/x-zip-compressed",class:"file-input",onChange:c=>je(r.key,c)},null,40,va),I(L(Z),{icon:"pi pi-upload",label:"رفع صور جديدة",loading:B.value===r.key,disabled:$.value===r.key,class:"upload-btn btn-add",onClick:c=>Re(r.key)},null,8,["loading","disabled","onClick"]),I(L(Z),{icon:"pi pi-file-import",label:"رفع ملف مضغوط",severity:"secondary",loading:$.value===r.key,disabled:B.value===r.key,class:"upload-btn upload-btn--zip",onClick:c=>Ue(r.key)},null,8,["loading","disabled","onClick"]),$.value===r.key?(i(),l("span",fa,[...s[10]||(s[10]=[n("i",{class:"pi pi-spin pi-spinner"},null,-1),O(" جاري رفع ZIP إلى Vinstack… ",-1)])])):f("",!0)]),se(r.key).length?(i(),l("div",ma,[(i(!0),l(R,null,H(se(r.key),c=>(i(),l("div",{key:c,class:M(["thumb-card",{"thumb-card--uploaded":W(c)}])},[n("button",{type:"button",class:"thumb-btn",onClick:X=>ge(c)},[n("img",{src:c,alt:E.value,loading:"lazy",decoding:"async"},null,8,ya)],8,ha),W(c)?(i(),l("span",ga,"مرفوعة من الإدارة")):(i(),l("span",ba,"Vinstack")),W(c)?(i(),V(L(Z),{key:2,icon:"pi pi-trash",severity:"danger",rounded:"",size:"small",class:"thumb-delete",loading:m.value===re(c),"aria-label":"حذف الصورة",onClick:X=>Ne(c)},null,8,["loading","onClick"])):f("",!0)],2))),128))])):(i(),l("p",ka,[...s[11]||(s[11]=[n("i",{class:"pi pi-image"},null,-1),O(" لا توجد صور — ارفع صوراً للتاجر ",-1)])]))],42,ia))),128)),I(ke,{visible:u.value,"onUpdate:visible":s[0]||(s[0]=r=>u.value=r),vehicle:w.value??e.vehicle,"start-url":D.value,"api-mode":e.apiMode},null,8,["visible","vehicle","start-url","api-mode"])],64)):e.compact?(i(),V(ze,{key:5,vehicle:w.value??e.vehicle,"api-mode":e.apiMode,"show-button":"",onGalleryUpdated:Ve},null,8,["vehicle","api-mode"])):(i(),l(R,{key:6},[s[20]||(s[20]=n("header",{class:"photos-section-header photos-section-header--dealer"},[n("h3",{class:"photos-section-title"},"صور السيارة")],-1)),P.value?(i(),l("div",wa,[n("img",{src:P.value,alt:E.value,class:"preview-img",loading:"lazy",decoding:"async"},null,8,$a),s[14]||(s[14]=n("span",{class:"preview-label"},"معاينة",-1))])):f("",!0),j.value.length?(i(),l("div",xa,[n("div",Ca,[n("button",{type:"button",class:"gallery-nav gallery-nav--prev",disabled:S.value===0,"aria-label":"الصورة السابقة",onClick:Ke},[...s[15]||(s[15]=[n("i",{class:"pi pi-chevron-right"},null,-1)])],8,La),n("button",{type:"button",class:"main-photo",onClick:s[1]||(s[1]=r=>ge(Y.value))},[Y.value?(i(),l("img",{key:Y.value,src:Y.value,alt:E.value,decoding:"async"},null,8,Ia)):f("",!0),W(Y.value)?(i(),l("span",Sa,"مرفوعة من الإدارة")):f("",!0),s[16]||(s[16]=n("span",{class:"zoom-hint"},[n("i",{class:"pi pi-search-plus"}),O(" تكبير")],-1))]),n("button",{type:"button",class:"gallery-nav gallery-nav--next",disabled:S.value>=j.value.length-1,"aria-label":"الصورة التالية",onClick:He},[...s[17]||(s[17]=[n("i",{class:"pi pi-chevron-left"},null,-1)])],8,za)]),j.value.length>1?(i(),l("div",Pa,[(i(!0),l(R,null,H(j.value,(r,c)=>(i(),l("button",{key:`${c}-${r}`,type:"button",class:M(["gallery-thumb-btn",{active:c===S.value}]),onClick:X=>oe(c)},[n("img",{src:r,alt:`${E.value} thumbnail`,loading:"lazy",decoding:"async"},null,8,Ba),W(r)?(i(),l("span",_a)):f("",!0)],10,Da))),128))])):f("",!0),n("p",Va,h(S.value+1)+" / "+h(j.value.length),1)])):P.value?(i(),l("div",Aa,[...s[18]||(s[18]=[n("i",{class:"pi pi-info-circle"},null,-1),n("span",null,"لا توجد صور عالية الدقة — المعاينة فقط متاحة",-1)])])):(i(),l("div",Ea,[...s[19]||(s[19]=[n("i",{class:"pi pi-image"},null,-1),n("span",null,"لا توجد صور لهذه السيارة",-1)])])),I(ke,{visible:u.value,"onUpdate:visible":s[2]||(s[2]=r=>u.value=r),vehicle:w.value,"start-url":D.value,"api-mode":e.apiMode},null,8,["visible","vehicle","start-url","api-mode"])],64))]))}},Ua=le(Ra,[["__scopeId","data-v-b225fc05"]]),Ta={key:0,class:"drawer-header"},Ma={class:"drawer-header-top"},Oa={class:"drawer-title"},Za={class:"drawer-header-meta"},Fa={key:0,class:"stale-note"},ja={key:1,class:"drawer-header drawer-header--loading"},Na={key:0,class:"drawer-loading"},Ga={key:1,class:"drawer-error"},Ka={key:2,class:"drawer-body"},Ha={class:"section-title"},qa={class:"field-grid"},Wa={key:0,class:"detail-section"},Ja={class:"field-grid"},Ya={key:1,class:"detail-section"},Qa={key:0,class:"record-list"},Xa={class:"record-title"},ei={key:0,class:"record-sub"},ti={key:1,class:"empty-section"},ni={class:"detail-section"},ai={key:0,class:"record-list"},ii=["href"],li={key:1,class:"empty-section"},si={__name:"VehicleDetailDrawer",props:{visible:{type:Boolean,default:!1},vehicleId:{type:[Number,String],default:null},mode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)}},emits:["update:visible"],setup(e,{emit:t}){const a=e,v=t,y=C(!1),p=C(null),u=C(null),D=b({get:()=>a.visible,set:o=>v("update:visible",o)}),B=b(()=>De(u.value?.status)),$=b(()=>a.mode==="admin"),m=b(()=>{const o=u.value??{};return{id:o.id??a.vehicleId,vin:o.vin,year:o.year,make:o.make,model:o.model,images:o.images??[],images_by_stage:o.images_by_stage,uploaded_images:o.uploaded_images??[],raw_data:{thumbnail_url:o.thumbnail_url,images:o.images,images_by_stage:o.images_by_stage,uploaded_images:o.uploaded_images}}});function _(o){!o||!u.value||(u.value.images=o.images??u.value.images,u.value.images_by_stage=o.images_by_stage??u.value.images_by_stage,u.value.uploaded_images=o.uploaded_images??u.value.uploaded_images,u.value.thumbnail_url=o.thumbnail_url??u.value.thumbnail_url)}const z=b(()=>`${a.mode==="dealer"?"/dealer/vehicles":"/admin/vehicles"}/${a.vehicleId}/details`);fe(()=>[a.visible,a.vehicleId],([o,k])=>{o&&k&&A(),o||(u.value=null,p.value=null)});async function A(){if(a.vehicleId){y.value=!0,p.value=null;try{const{data:o}=await Se.get(z.value);u.value=o.data}catch(o){p.value=o.response?.data?.message||"Failed to load vehicle details.",u.value=null}finally{y.value=!1}}}function S(){D.value=!1}function q(){u.value=null,p.value=null}function N(o){return xe(o)||"—"}function w(o){if(o.value===null||o.value===void 0||o.value==="")return"—";if(o.type==="date")return xe(o.value)||"—";if(o.type==="money"){const k=Number(o.value);if(Number.isFinite(k))return new Intl.NumberFormat(void 0,{style:"currency",currency:"USD",maximumFractionDigits:0}).format(k)}return String(o.value)}function G(o){return o.number||o.invoice_number||o.id||"Invoice"}function U(o){const k=[o.status,o.amount,o.date||o.created_at].filter(Boolean);return k.length?k.join(" · "):null}function F(o){return o.name||o.title||o.filename||o.type||"Document"}return(o,k)=>(i(),V(L(_e),{visible:D.value,"onUpdate:visible":k[0]||(k[0]=x=>D.value=x),position:"right",style:{width:"min(480px, 100vw)"},pt:{root:{class:"vehicle-detail-drawer"}},showCloseIcon:!1,onHide:q},{header:ee(()=>[u.value?(i(),l("div",Ta,[n("div",Ma,[n("h2",Oa,h(u.value.title||"—"),1),I(L(Z),{icon:"pi pi-times",text:"",rounded:"",severity:"secondary","aria-label":"Close",onClick:S})]),n("div",Za,[u.value.status?(i(),l("span",{key:0,class:M(["status-pill",B.value])},[k[1]||(k[1]=n("span",{class:"status-dot"},null,-1)),O(" "+h(u.value.status),1)],2)):f("",!0),u.value.local_status?(i(),V(L(Be),{key:1,value:u.value.local_status,class:"local-tag"},null,8,["value"])):f("",!0)]),I(Pe,{vin:u.value.vin,class:"drawer-vin"},null,8,["vin"]),!u.value.vinstack_fresh&&!["manual","nujoom_al_jazeera"].includes(u.value.source)?(i(),l("div",Fa,[...k[2]||(k[2]=[n("i",{class:"pi pi-info-circle"},null,-1),O(" Showing cached data — Vinstack live fetch unavailable. ",-1)])])):f("",!0)])):(i(),l("div",ja,[I(L($e),{width:"70%",height:"1.4rem"}),I(L($e),{width:"40%",height:"1rem",class:"mt-sm"})]))]),default:ee(()=>[y.value?(i(),l("div",Na,[I(L(ie),{style:{width:"36px",height:"36px"}})])):p.value?(i(),l("div",Ga,[k[3]||(k[3]=n("i",{class:"pi pi-exclamation-circle"},null,-1)),n("span",null,h(p.value),1),I(L(Z),{label:"Retry",size:"small",outlined:"",onClick:A})])):u.value?(i(),l("div",Ka,[I(Ua,{vehicle:m.value,"admin-mode":$.value,"api-mode":e.mode,onUpdated:_},null,8,["vehicle","admin-mode","api-mode"]),(i(!0),l(R,null,H(u.value.sections,x=>(i(),l("section",{key:x.key,class:"detail-section"},[n("h3",Ha,h(x.title),1),n("dl",qa,[(i(!0),l(R,null,H(x.fields,P=>(i(),l(R,{key:`${x.key}-${P.key}`},[n("dt",null,h(P.label),1),n("dd",null,h(w(P)),1)],64))),128))])]))),128)),u.value.assignment?.dealer_name?(i(),l("section",Wa,[k[6]||(k[6]=n("h3",{class:"section-title"},"Assignment",-1)),n("dl",Ja,[k[4]||(k[4]=n("dt",null,"Dealer",-1)),n("dd",null,h(u.value.assignment.dealer_name),1),k[5]||(k[5]=n("dt",null,"Assigned",-1)),n("dd",null,h(N(u.value.assignment.assigned_at)),1)])])):f("",!0),e.mode!=="dealer"?(i(),l("section",Ya,[k[7]||(k[7]=n("h3",{class:"section-title"},"Invoices",-1)),u.value.invoices?.length?(i(),l("div",Qa,[(i(!0),l(R,null,H(u.value.invoices,(x,P)=>(i(),l("div",{key:x.id??P,class:"record-item"},[n("div",Xa,h(G(x)),1),U(x)?(i(),l("div",ei,h(U(x)),1)):f("",!0)]))),128))])):(i(),l("p",ti,"—"))])):f("",!0),n("section",ni,[k[9]||(k[9]=n("h3",{class:"section-title"},"Documents",-1)),u.value.documents?.length?(i(),l("div",ai,[(i(!0),l(R,null,H(u.value.documents,(x,P)=>(i(),l("a",{key:x.id??x.url??P,href:x.url||x.link||"#",class:"record-item record-link",target:"_blank",rel:"noopener noreferrer"},[k[8]||(k[8]=n("i",{class:"pi pi-file"},null,-1)),n("span",null,h(F(x)),1)],8,ii))),128))])):(i(),l("p",li,"—"))])])):f("",!0)]),_:1},8,["visible"]))}},fi=le(si,[["__scopeId","data-v-b0ec30eb"]]);export{vi as V,fi as a,_e as b,Ua as c,on as s};
