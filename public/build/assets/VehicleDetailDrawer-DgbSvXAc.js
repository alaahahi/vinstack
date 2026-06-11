import{a as qe}from"./index-DHwpnshG.js";import{B as Le,I as We,J as Je,s as F,ag as Ye,aa as Qe,K as Ie,ah as Xe,ai as et,ab as tt,Z as ue,aj as nt,m as be,a0 as at,o as a,c as _,w as ee,k as l,x as O,d as I,a2 as it,h as lt,q as Y,F as R,f as n,n as Z,t as m,l as f,a1 as st,i as T,g as te,e as L,E as b,p as H,ak as ce,r as C,a6 as Se,a as rt,D as ot,j as dt,y as fe}from"./app-xPFgM0gJ.js";import{V as ze,e as De,v as ut,f as ct,g as pt,h as vt,i as ft,j as mt,k as ht,l as yt,m as gt,n as bt,o as kt,p as wt,q as Pe,r as $t,t as xt,u as Ct,w as Lt,x as It,y as St,z as zt,A as Dt,B as Pt,D as Bt,C as _t,E as Vt,G as At,_ as ke,F as we,H as Et,I as pe,J as Rt,K as Tt,L as Ut,M as Mt,N as Ot,O as Zt,P as Ft,Q as jt,R as Nt,S as $e,T as xe}from"./useInfiniteScroll-CzkSMqFE.js";import{s as ie}from"./index-CxBcyBzJ.js";import{s as Be}from"./index-UfGVROUA.js";import{_ as le}from"./_plugin-vue_export-helper-DlAUqK2U.js";var Gt=`
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
`,Kt={mask:function(t){var i=t.position,v=t.modal;return{position:"fixed",height:"100%",width:"100%",left:0,top:0,display:"flex",justifyContent:i==="left"?"flex-start":i==="right"?"flex-end":"center",alignItems:i==="top"?"flex-start":i==="bottom"?"flex-end":"center",pointerEvents:v?"auto":"none"}},root:{pointerEvents:"auto"}},Ht={mask:function(t){var i=t.instance,v=t.props,g=["left","right","top","bottom"],p=g.find(function(u){return u===v.position});return["p-drawer-mask",{"p-overlay-mask p-overlay-mask-enter-active":v.modal,"p-drawer-open":i.containerVisible,"p-drawer-full":i.fullScreen},p?"p-drawer-".concat(p):""]},root:function(t){var i=t.instance;return["p-drawer p-component",{"p-drawer-full":i.fullScreen}]},header:"p-drawer-header",title:"p-drawer-title",pcCloseButton:"p-drawer-close-button",content:"p-drawer-content",footer:"p-drawer-footer"},qt=Le.extend({name:"drawer",style:Gt,classes:Ht,inlineStyles:Kt}),Wt={name:"BaseDrawer",extends:Qe,props:{visible:{type:Boolean,default:!1},position:{type:String,default:"left"},header:{type:null,default:null},baseZIndex:{type:Number,default:0},autoZIndex:{type:Boolean,default:!0},dismissable:{type:Boolean,default:!0},showCloseIcon:{type:Boolean,default:!0},closeButtonProps:{type:Object,default:function(){return{severity:"secondary",text:!0,rounded:!0}}},closeIcon:{type:String,default:void 0},modal:{type:Boolean,default:!0},blockScroll:{type:Boolean,default:!1},closeOnEscape:{type:Boolean,default:!0}},style:qt,provide:function(){return{$pcDrawer:this,$parentInstance:this}}};function ne(e){"@babel/helpers - typeof";return ne=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ne(e)}function ve(e,t,i){return(t=Jt(t))in e?Object.defineProperty(e,t,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[t]=i,e}function Jt(e){var t=Yt(e,"string");return ne(t)=="symbol"?t:t+""}function Yt(e,t){if(ne(e)!="object"||!e)return e;var i=e[Symbol.toPrimitive];if(i!==void 0){var v=i.call(e,t);if(ne(v)!="object")return v;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var _e={name:"Drawer",extends:Wt,inheritAttrs:!1,emits:["update:visible","show","after-show","hide","after-hide","before-hide"],data:function(){return{containerVisible:this.visible}},container:null,mask:null,content:null,headerContainer:null,footerContainer:null,closeButton:null,outsideClickListener:null,documentKeydownListener:null,watch:{dismissable:function(t){t&&!this.modal?this.bindOutsideClickListener():this.unbindOutsideClickListener()}},updated:function(){this.visible&&(this.containerVisible=this.visible)},beforeUnmount:function(){this.disableDocumentSettings(),this.mask&&this.autoZIndex&&ue.clear(this.mask),this.container=null,this.mask=null},methods:{hide:function(){this.$emit("update:visible",!1)},onEnter:function(){this.$emit("show"),this.focus(),this.bindDocumentKeyDownListener(),this.autoZIndex&&ue.set("modal",this.mask,this.baseZIndex||this.$primevue.config.zIndex.modal)},onAfterEnter:function(){this.enableDocumentSettings(),this.$emit("after-show")},onBeforeLeave:function(){this.modal&&!this.isUnstyled&&nt(this.mask,"p-overlay-mask-leave-active"),this.$emit("before-hide")},onLeave:function(){this.$emit("hide")},onAfterLeave:function(){this.autoZIndex&&ue.clear(this.mask),this.unbindDocumentKeyDownListener(),this.containerVisible=!1,this.disableDocumentSettings(),this.$emit("after-hide")},onMaskClick:function(t){this.dismissable&&this.modal&&this.mask===t.target&&this.hide()},focus:function(){var t=function(g){return g&&g.querySelector("[autofocus]")},i=this.$slots.header&&t(this.headerContainer);i||(i=this.$slots.default&&t(this.container),i||(i=this.$slots.footer&&t(this.footerContainer),i||(i=this.closeButton))),i&&tt(i)},enableDocumentSettings:function(){this.dismissable&&!this.modal&&this.bindOutsideClickListener(),this.blockScroll&&et()},disableDocumentSettings:function(){this.unbindOutsideClickListener(),this.blockScroll&&Xe()},onKeydown:function(t){t.code==="Escape"&&this.closeOnEscape&&this.hide()},containerRef:function(t){this.container=t},maskRef:function(t){this.mask=t},contentRef:function(t){this.content=t},headerContainerRef:function(t){this.headerContainer=t},footerContainerRef:function(t){this.footerContainer=t},closeButtonRef:function(t){this.closeButton=t?t.$el:void 0},bindDocumentKeyDownListener:function(){this.documentKeydownListener||(this.documentKeydownListener=this.onKeydown,document.addEventListener("keydown",this.documentKeydownListener))},unbindDocumentKeyDownListener:function(){this.documentKeydownListener&&(document.removeEventListener("keydown",this.documentKeydownListener),this.documentKeydownListener=null)},bindOutsideClickListener:function(){var t=this;this.outsideClickListener||(this.outsideClickListener=function(i){t.isOutsideClicked(i)&&t.hide()},document.addEventListener("click",this.outsideClickListener,!0))},unbindOutsideClickListener:function(){this.outsideClickListener&&(document.removeEventListener("click",this.outsideClickListener,!0),this.outsideClickListener=null)},isOutsideClicked:function(t){return this.container&&!this.container.contains(t.target)}},computed:{fullScreen:function(){return this.position==="full"},closeAriaLabel:function(){return this.$primevue.config.locale.aria?this.$primevue.config.locale.aria.close:void 0},dataP:function(){return Ie(ve(ve(ve({"full-screen":this.position==="full"},this.position,this.position),"open",this.containerVisible),"modal",this.modal))}},directives:{focustrap:Ye},components:{Button:F,Portal:Je,TimesIcon:We}},Qt=["data-p"],Xt=["role","aria-modal","data-p"];function en(e,t,i,v,g,p){var u=be("Button"),z=be("Portal"),V=at("focustrap");return a(),_(z,null,{default:ee(function(){return[g.containerVisible?(a(),l("div",O({key:0,ref:p.maskRef,onMousedown:t[0]||(t[0]=function(){return p.onMaskClick&&p.onMaskClick.apply(p,arguments)}),class:e.cx("mask"),style:e.sx("mask",!0,{position:e.position,modal:e.modal}),"data-p":p.dataP},e.ptm("mask")),[I(it,O({name:"p-drawer",onEnter:p.onEnter,onAfterEnter:p.onAfterEnter,onBeforeLeave:p.onBeforeLeave,onLeave:p.onLeave,onAfterLeave:p.onAfterLeave,appear:""},e.ptm("transition")),{default:ee(function(){return[e.visible?lt((a(),l("div",O({key:0,ref:p.containerRef,class:e.cx("root"),style:e.sx("root"),role:e.modal?"dialog":"complementary","aria-modal":e.modal?!0:void 0,"data-p":p.dataP},e.ptmi("root")),[e.$slots.container?Y(e.$slots,"container",{key:0,closeCallback:p.hide}):(a(),l(R,{key:1},[n("div",O({ref:p.headerContainerRef,class:e.cx("header")},e.ptm("header")),[Y(e.$slots,"header",{class:Z(e.cx("title"))},function(){return[e.header?(a(),l("div",O({key:0,class:e.cx("title")},e.ptm("title")),m(e.header),17)):f("",!0)]}),e.showCloseIcon?Y(e.$slots,"closebutton",{key:0,closeCallback:p.hide},function(){return[I(u,O({ref:p.closeButtonRef,type:"button",class:e.cx("pcCloseButton"),"aria-label":p.closeAriaLabel,unstyled:e.unstyled,onClick:p.hide},e.closeButtonProps,{pt:e.ptm("pcCloseButton"),"data-pc-group-section":"iconcontainer"}),{icon:ee(function(w){return[Y(e.$slots,"closeicon",{},function(){return[(a(),_(st(e.closeIcon?"span":"TimesIcon"),O({class:[e.closeIcon,w.class]},e.ptm("pcCloseButton").icon),null,16,["class"]))]})]}),_:3},16,["class","aria-label","unstyled","onClick","pt"])]}):f("",!0)],16),n("div",O({ref:p.contentRef,class:e.cx("content")},e.ptm("content")),[Y(e.$slots,"default")],16),e.$slots.footer?(a(),l("div",O({key:0,ref:p.footerContainerRef,class:e.cx("footer")},e.ptm("footer")),[Y(e.$slots,"footer")],16)):f("",!0)],64))],16,Xt)),[[V]]):f("",!0)]}),_:3},16,["onEnter","onAfterEnter","onBeforeLeave","onLeave","onAfterLeave"])],16,Qt)):f("",!0)]}),_:3})}_e.render=en;var tn=`
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
`,nn={root:function(t){var i=t.instance,v=t.props;return["p-textarea p-component",{"p-filled":i.$filled,"p-textarea-resizable ":v.autoResize,"p-textarea-sm p-inputfield-sm":v.size==="small","p-textarea-lg p-inputfield-lg":v.size==="large","p-invalid":i.$invalid,"p-variant-filled":i.$variant==="filled","p-textarea-fluid":i.$fluid}]}},an=Le.extend({name:"textarea",style:tn,classes:nn}),ln={name:"BaseTextarea",extends:qe,props:{autoResize:Boolean},style:an,provide:function(){return{$pcTextarea:this,$parentInstance:this}}};function ae(e){"@babel/helpers - typeof";return ae=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ae(e)}function sn(e,t,i){return(t=rn(t))in e?Object.defineProperty(e,t,{value:i,enumerable:!0,configurable:!0,writable:!0}):e[t]=i,e}function rn(e){var t=on(e,"string");return ae(t)=="symbol"?t:t+""}function on(e,t){if(ae(e)!="object"||!e)return e;var i=e[Symbol.toPrimitive];if(i!==void 0){var v=i.call(e,t);if(ae(v)!="object")return v;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var dn={name:"Textarea",extends:ln,inheritAttrs:!1,observer:null,mounted:function(){var t=this;this.autoResize&&(this.observer=new ResizeObserver(function(){requestAnimationFrame(function(){t.resize()})}),this.observer.observe(this.$el))},updated:function(){this.autoResize&&this.resize()},beforeUnmount:function(){this.observer&&this.observer.disconnect()},methods:{resize:function(){if(this.$el.offsetParent){var t=this.$el.style.height,i=parseInt(t)||0,v=this.$el.scrollHeight,g=!i||v>i,p=i&&v<i;p?(this.$el.style.height="auto",this.$el.style.height="".concat(this.$el.scrollHeight,"px")):g&&(this.$el.style.height="".concat(v,"px"))}},onInput:function(t){this.autoResize&&this.resize(),this.writeValue(t.target.value,t)}},computed:{attrs:function(){return O(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return Ie(sn({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},un=["value","name","disabled","aria-invalid","data-p"];function cn(e,t,i,v,g,p){return a(),l("textarea",O({class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.invalid||void 0,"data-p":p.dataP,onInput:t[0]||(t[0]=function(){return p.onInput&&p.onInput.apply(p,arguments)})},p.attrs),null,16,un)}dn.render=cn;const pn={class:"vehicle-row"},vn={class:"cell cell-vehicle"},fn={class:"vehicle-info"},mn={class:"title-line"},hn=["title"],yn={key:2,class:"pi pi-images local-upload-hint",title:"يوجد صور مرفوعة محلياً للتاجر","aria-label":"صور مرفوعة محلياً"},gn={class:"entered-by"},bn={class:"cell cell-lot"},kn={class:"lot-id"},wn={class:"auction"},$n={class:"cell cell-route"},xn={key:0,class:"route-line"},Cn={key:1,class:"route-line"},Ln={class:"cell cell-refs"},In={key:0,class:"ref-line ref-line--container"},Sn={class:"ref-container-num"},zn={key:1,class:"ref-line"},Dn={class:"ref-badges"},Pn={class:"mini-badge mini-badge--neutral"},Bn={class:"cell cell-dates"},_n={class:"date-row"},Vn={class:"date-value"},An={class:"date-row"},En={class:"date-value"},Rn={key:0,class:"cell cell-admin"},Tn={key:1,class:"dealer-tag"},Un={class:"dealer-tag__name"},Mn={key:1,class:"cell cell-actions"},On={__name:"VehicleListRow",props:{vehicle:{type:Object,required:!0},mode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)},trackingAvailable:{type:Boolean,default:!1}},emits:["assign","update-status","open-detail","edit","unassign","track-container"],setup(e){const t=e,i=b(()=>t.vehicle?.source==="manual"),v=b(()=>ut(t.vehicle)),g=b(()=>ct(t.vehicle)),p=b(()=>pt(t.vehicle)),u=b(()=>vt(t.vehicle)),z=b(()=>ft(t.vehicle)),V=b(()=>(t.vehicle?.uploaded_images?.length??0)>0),w=b(()=>mt(t.vehicle)),h=b(()=>ht(w.value)),B=b(()=>yt(t.vehicle)),D=b(()=>gt(t.vehicle)),A=b(()=>bt(t.vehicle)),S=b(()=>kt(t.vehicle)),G=b(()=>wt(t.vehicle)),q=b(()=>Pe(G.value)),$=b(()=>$t(t.vehicle)),j=b(()=>$.value?t.trackingAvailable:!1),N=b(()=>xt(t.vehicle)),E=b(()=>Ct(t.vehicle)),o=b(()=>Lt(t.vehicle)),k=b(()=>It(t.vehicle)),x=b(()=>St(t.vehicle)),P=b(()=>zt(t.vehicle)),U=b(()=>t.vehicle.active_assignment?.dealer?.company_name??null),W=b(()=>Dt(t.vehicle));return(K,y)=>(a(),l("div",pn,[n("div",vn,[I(ze,{vehicle:e.vehicle,variant:"row"},null,8,["vehicle"]),n("div",fn,[n("div",mn,[n("button",{type:"button",class:"title title-link",onClick:y[0]||(y[0]=M=>K.$emit("open-detail",e.vehicle))},m(u.value||"—"),1),w.value?(a(),l("span",{key:0,class:Z(["fuel-badge",h.value])},m(w.value),3)):f("",!0),n("span",{class:Z(["source-pill",p.value])},m(g.value),3),z.value>1?(a(),l("span",{key:1,class:"gallery-pill",title:`${z.value} صورة HD`},[y[6]||(y[6]=n("i",{class:"pi pi-images"},null,-1)),T(" "+m(z.value),1)],8,hn)):f("",!0),e.mode==="admin"&&V.value?(a(),l("i",yn)):f("",!0)]),I(De,{vin:e.vehicle.vin,class:"vehicle-vin-line"},null,8,["vin"]),n("div",gn,"Entered by "+m(P.value),1)])]),n("div",bn,[n("div",kn,m(B.value||"—"),1),n("div",wn,m(D.value||"—"),1)]),n("div",$n,[A.value?(a(),l("div",xn,[y[7]||(y[7]=n("span",{class:"route-dot route-dot--origin"},null,-1)),n("span",null,m(A.value),1)])):f("",!0),S.value?(a(),l("div",Cn,[y[8]||(y[8]=n("i",{class:"pi pi-map-marker route-pin"},null,-1)),n("span",null,m(S.value),1)])):f("",!0),G.value?(a(),l("span",{key:2,class:Z(["status-pill",q.value])},[y[9]||(y[9]=n("span",{class:"status-dot"},null,-1)),T(" "+m(G.value),1)],2)):f("",!0)]),n("div",Ln,[$.value?(a(),l("div",In,[y[10]||(y[10]=n("i",{class:"pi pi-box ref-icon"},null,-1)),n("span",Sn,m($.value),1),I(L(F),{icon:"pi pi-map-marker",severity:j.value?"info":"secondary",text:"",rounded:"",size:"small",disabled:!j.value,class:Z(["track-btn",{"track-btn--ready":j.value}]),"aria-label":"تتبع الحاوية",title:"تتبع الحاوية",onClick:y[1]||(y[1]=te(M=>K.$emit("track-container",e.vehicle),["stop"]))},null,8,["severity","disabled","class"])])):f("",!0),N.value?(a(),l("div",zn,[y[11]||(y[11]=n("i",{class:"pi pi-file ref-icon"},null,-1)),n("span",null,m(N.value),1)])):f("",!0),n("div",Dn,[E.value.label?(a(),l("span",{key:0,class:Z(["mini-badge",E.value.present?"mini-badge--ok":"mini-badge--bad"])},[y[12]||(y[12]=n("i",{class:"pi pi-key"},null,-1)),T(" "+m(E.value.label),1)],2)):f("",!0),n("span",Pn,[y[13]||(y[13]=n("i",{class:"pi pi-file"},null,-1)),T(" "+m(o.value),1)])])]),n("div",Bn,[n("div",_n,[y[14]||(y[14]=n("span",{class:"date-label"},"Purchase",-1)),n("span",Vn,m(k.value||"—"),1)]),n("div",An,[y[15]||(y[15]=n("span",{class:"date-label"},"Arrived terminal",-1)),n("span",En,m(x.value||"—"),1)])]),e.mode==="admin"?(a(),l("div",Rn,[e.vehicle.status?(a(),l("span",{key:0,class:Z(["status-pill assignment-pill",W.value])},[y[16]||(y[16]=n("span",{class:"status-dot"},null,-1)),T(" "+m(e.vehicle.status),1)],2)):f("",!0),U.value?(a(),l("div",Tn,[n("span",Un,m(U.value),1),n("button",{type:"button",class:"dealer-tag__remove",title:"إلغاء الإسناد","aria-label":"إلغاء إسناد التاجر",onClick:y[2]||(y[2]=te(M=>K.$emit("unassign",e.vehicle),["stop"]))},[...y[17]||(y[17]=[n("i",{class:"pi pi-times"},null,-1)])])])):f("",!0),i.value?(a(),_(L(F),{key:2,icon:"pi pi-pencil",label:"تعديل",size:"small",severity:"secondary",outlined:"",title:"تعديل سيارة يدوية",onClick:y[3]||(y[3]=M=>K.$emit("edit",e.vehicle))})):f("",!0),v.value?f("",!0):(a(),_(L(F),{key:3,label:"إسناد",size:"small",class:"btn-assign",onClick:y[4]||(y[4]=M=>K.$emit("assign",e.vehicle))}))])):(a(),l("div",Mn,[I(L(Be),{value:e.vehicle.status,class:"local-tag"},null,8,["value"]),I(L(F),{icon:"pi pi-pencil",severity:"secondary",text:"",rounded:"",title:"تحديث الحالة",onClick:y[5]||(y[5]=M=>K.$emit("update-status",e.vehicle))})]))]))}},Zn=le(On,[["__scopeId","data-v-4cc45975"]]),Fn={class:"vehicle-list-panel"},jn={key:0,class:"list-header"},Nn={key:1,class:"list-loading"},Gn={key:2,class:"list-empty"},Kn={key:0,class:"empty-hint"},Hn={key:3,class:"list-body"},qn={__name:"VehicleListPanel",props:{vehicles:{type:Array,default:()=>[]},loading:{type:Boolean,default:!1},loadingMore:{type:Boolean,default:!1},total:{type:Number,default:0},page:{type:Number,default:1},perPage:{type:Number,default:50},mode:{type:String,default:"admin"},showHeader:{type:Boolean,default:!0},emptyText:{type:String,default:"لا توجد سيارات مسندة إليك"},emptyHint:{type:String,default:""},emptyActionLabel:{type:String,default:""},infiniteScroll:{type:Boolean,default:!1},hasMore:{type:Boolean,default:!1},trackingAvailable:{type:Boolean,default:!1}},emits:["assign","unassign","update-status","open-detail","edit","page","empty-action","load-more"],setup(e,{emit:t}){const i=e,v=t,{sentinel:g}=Pt({enabled:ce(i,"infiniteScroll"),hasMore:ce(i,"hasMore"),loading:ce(i,"loadingMore"),onLoadMore:()=>v("load-more")}),p=g,u=C(!1),z=C(null);function V(w){const h=Vt(w);h&&(z.value=h,u.value=!0)}return(w,h)=>(a(),l("div",Fn,[e.showHeader?(a(),l("div",jn,[h[8]||(h[8]=n("span",null,"Vehicle",-1)),h[9]||(h[9]=n("span",null,"ID & source",-1)),h[10]||(h[10]=n("span",null,"Route & status",-1)),h[11]||(h[11]=n("span",null,"References",-1)),h[12]||(h[12]=n("span",null,"Dates",-1)),n("span",null,m(e.mode==="admin"?"Assignment":"Actions"),1)])):f("",!0),e.loading&&!e.vehicles.length?(a(),l("div",Nn,[I(L(ie),{style:{width:"36px",height:"36px"}})])):e.vehicles.length?(a(),l("div",Hn,[(a(!0),l(R,null,H(e.vehicles,B=>(a(),_(Zn,{key:B.id,vehicle:B,mode:e.mode,"tracking-available":e.trackingAvailable,onAssign:h[1]||(h[1]=D=>w.$emit("assign",D)),onUnassign:h[2]||(h[2]=D=>w.$emit("unassign",D)),onUpdateStatus:h[3]||(h[3]=D=>w.$emit("update-status",D)),onOpenDetail:h[4]||(h[4]=D=>w.$emit("open-detail",D)),onEdit:h[5]||(h[5]=D=>w.$emit("edit",D)),onTrackContainer:V},null,8,["vehicle","mode","tracking-available"]))),128)),e.infiniteScroll&&e.hasMore?(a(),l("div",{key:0,ref_key:"sentinelRef",ref:p,class:"list-sentinel","aria-hidden":"true"},[e.loadingMore?(a(),_(L(ie),{key:0,style:{width:"28px",height:"28px"}})):f("",!0)],512)):f("",!0)])):(a(),l("div",Gn,[h[13]||(h[13]=n("i",{class:"pi pi-car"},null,-1)),n("span",null,m(e.emptyText),1),e.emptyHint?(a(),l("p",Kn,m(e.emptyHint),1)):f("",!0),e.emptyActionLabel?(a(),_(L(F),{key:1,label:e.emptyActionLabel,icon:"pi pi-refresh",outlined:"",size:"small",onClick:h[0]||(h[0]=B=>w.$emit("empty-action"))},null,8,["label"])):f("",!0)])),!e.infiniteScroll&&e.total>e.perPage?(a(),_(L(Bt),{key:4,rows:e.perPage,"total-records":e.total,first:(e.page-1)*e.perPage,template:"FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink",class:"list-paginator",onPage:h[6]||(h[6]=B=>w.$emit("page",B))},null,8,["rows","total-records","first"])):f("",!0),e.trackingAvailable?(a(),_(_t,{key:5,visible:u.value,"onUpdate:visible":h[7]||(h[7]=B=>u.value=B),"api-role":e.mode,container:z.value},null,8,["visible","api-role","container"])):f("",!0)]))}},mi=le(qn,[["__scopeId","data-v-592b36b5"]]);function Wn(e){const t=e?.response?.data;if(!t)return e?.message||"تعذّر رفع ملف ZIP إلى Vinstack";if(t.errors&&typeof t.errors=="object"){const g=Object.values(t.errors).flat()[0];if(g)return String(g)}let i=t.message||"تعذّر رفع ملف ZIP إلى Vinstack";const v=t.failed??t.data?.failed??[];if(v.length){const g=v.slice(0,3).map(p=>`${p.name}: ${p.error}`).join(" — ");i=`${i} (${g}${v.length>3?` +${v.length-3}`:""})`}return i}async function Jn(e,t,i){const v=new FormData;v.append("stage",t),v.append("zip",i,i.name);try{const{data:g}=await Se.post(`/admin/vehicles/${e}/images/zip`,v);return g}catch(g){throw g.message=Wn(g),g}}function Ce(e){if(!e)return!1;const t=String(e.name||"").toLowerCase();return e.type==="application/zip"||e.type==="application/x-zip-compressed"||t.endsWith(".zip")}const Yn={class:"photos-panel"},Qn={key:0,class:"gallery-loading"},Xn={key:1,class:"gallery-warning gallery-warning--danger"},ea={key:2,class:"gallery-warning gallery-warning--danger"},ta={key:3,class:"gallery-warning gallery-warning--ok"},na={key:0},aa={key:1},ia={key:0,class:"preview-block"},la=["src","alt"],sa=["onDragenter","onDragover","onDragleave","onDrop"],ra={class:"stage-card-header"},oa={class:"stage-card-title-wrap"},da={class:"stage-title"},ua={class:"stage-counts"},ca={class:"count-pill count-pill--vinstack",title:"صور من Vinstack"},pa={class:"count-pill count-pill--local",title:"صور مرفوعة من الإدارة"},va={class:"stage-upload-row"},fa=["onChange"],ma=["onChange"],ha={key:0,class:"zip-upload-progress"},ya={key:0,class:"stage-thumbs"},ga=["onClick"],ba=["src","alt"],ka={key:0,class:"source-tag source-tag--local"},wa={key:1,class:"source-tag source-tag--vinstack"},$a={key:1,class:"stage-empty"},xa={key:0,class:"preview-block"},Ca=["src","alt"],La={key:1,class:"gallery-block"},Ia={class:"gallery-main"},Sa=["disabled"],za=["src","alt"],Da={key:1,class:"local-badge-inline"},Pa=["disabled"],Ba={key:0,class:"gallery-thumb-strip"},_a=["onClick"],Va=["src","alt"],Aa={key:0,class:"local-dot",title:"مرفوعة من الإدارة"},Ea={class:"gallery-counter"},Ra={key:2,class:"no-hd"},Ta={key:3,class:"no-photos"},Ua={__name:"VehiclePhotosPanel",props:{vehicle:{type:Object,required:!0},compact:{type:Boolean,default:!1},adminMode:{type:Boolean,default:!1},apiMode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)}},emits:["updated"],setup(e,{emit:t}){const i=e,v=t,g=rt(),p=ot(),u=C(!1),z=C(null),V=C(null),w=C(null),h=C(null),B=C({}),D=C({}),A=C(null),S=C(0),G=C(!1),q=C(!1),$=C(null),j=C(!1),N=C(!1),E=C(null),o=C(0),k={gallery_token_missing:"توكن المعرض غير مضبوط — أضف Gallery Token في الإعدادات أو استخدم توكن المزامنة.",gallery_token_expired:"توكن المعرض منتهي — حدّثه من الإعدادات."},x=b(()=>{const d=E.value;return d?k[d]??d:""}),P=b(()=>Ot($.value??i.vehicle)),U=b(()=>Zt($.value??i.vehicle)),W=b(()=>Ft($.value??i.vehicle)),K=b(()=>jt($.value??i.vehicle)),y=b(()=>Nt($.value??i.vehicle)),M=b(()=>U.value[S.value]??null);async function Q(){const d=i.vehicle?.id,s=i.vehicle?.vin;if(!d||!s||!we(i.vehicle)){$.value=i.vehicle;return}q.value=!0,j.value=!1,N.value=!1,E.value=null,o.value=0;try{const r=await Et(d,i.apiMode);$.value=pe(i.vehicle,r),j.value=!!r.gallery_fresh,N.value=!!r.gallery_token_expired,E.value=r.gallery_error??null,o.value=Number(r.gallery_new_images_count??0),v("updated",$.value)}catch(r){$.value=i.vehicle,E.value=r.response?.data?.message||"تعذّر الاتصال بـ API المعرض — تُعرض الصور المخزّنة."}finally{q.value=!1}}function Ve(d,s){$.value=pe(i.vehicle,d),v("updated",$.value)}dt(Q),fe(()=>[i.vehicle?.id,i.vehicle?.vin],()=>{Q()}),fe(U,d=>{S.value>=d.length&&(S.value=Math.max(0,d.length-1))});function Ae(d,s){s&&(B.value[d]=s)}function Ee(d,s){s&&(D.value[d]=s)}function se(d){return K.value[d]??[]}function me(d){const s=se(d),r=s.filter(c=>J(c)).length;return{total:s.length,uploaded:r,vinstack:s.length-r}}function J(d){return Rt(d,y.value)}function re(d){return Tt(d,y.value)}function Re(d){B.value[d]?.click()}function Te(d){D.value[d]?.click()}function Ue(d){A.value=d}function Me(d){A.value=d}function Oe(d,s){s.currentTarget?.contains(s.relatedTarget)||A.value===d&&(A.value=null)}function Ze(d,s){A.value=null;const r=[...s.dataTransfer?.files??[]],c=r.find(de=>Ce(de));if(c){he(d,c);return}const X=r.filter(de=>de.type.startsWith("image/"));if(!X.length){g.add({severity:"warn",summary:"ملف غير مدعوم",detail:"اسحب صوراً أو ملف ZIP فقط",life:3500});return}ye(d,X)}async function Fe(d,s){const r=s.target,c=[...r.files??[]];r.value="",c.length&&await ye(d,c)}async function je(d,s){const r=s.target,c=r.files?.[0];if(r.value="",!!c){if(!Ce(c)){g.add({severity:"warn",summary:"ملف غير مدعوم",detail:"يُقبل ملف ZIP فقط",life:3500});return}await he(d,c)}}async function he(d,s){if(!(!s||!i.vehicle?.id)){if(!we(i.vehicle)){g.add({severity:"warn",summary:"غير متاح",detail:"رفع ZIP إلى Vinstack متاح لسيارات المزامنة فقط",life:4e3});return}w.value=d;try{const r=await Jn(i.vehicle.id,d,s),c=r.data?.gallery;c?($.value=pe(i.vehicle,c),j.value=!!c.gallery_fresh,N.value=!!c.gallery_token_expired,E.value=c.gallery_error??null,o.value=Number(c.gallery_new_images_count??r.data?.uploaded??0),v("updated",$.value)):await Q(),g.add({severity:"success",summary:"تم رفع ZIP",detail:r.message||"تم رفع الصور إلى Vinstack وتحديث المعرض",life:4500})}catch(r){g.add({severity:"error",summary:"فشل رفع ZIP",detail:r.message||"تعذّر رفع ملف ZIP إلى Vinstack",life:5e3})}finally{w.value=null}}}async function ye(d,s){if(!(!s.length||!i.vehicle?.id)){V.value=d;try{const r=await Ut(i.vehicle.id,d,s),c=r.data?.vehicle??r.data;v("updated",c),await Q(),g.add({severity:"success",summary:"تم الرفع",detail:r.message||"تم رفع الصور بنجاح وتحديث المعرض",life:3500})}catch(r){g.add({severity:"error",summary:"فشل الرفع",detail:r.response?.data?.message||"تعذر رفع الصور",life:4e3})}finally{V.value=null}}}function Ne(d){!re(d)||!i.vehicle?.id||p.require({message:"هل أنت متأكد من حذف هذه الصورة؟ لن يتمكن التاجر من رؤيتها بعد الحذف.",header:"حذف الصورة",icon:"pi pi-exclamation-triangle",rejectLabel:"إلغاء",acceptLabel:"حذف",acceptClass:"p-button-danger",accept:()=>Ge(d)})}async function Ge(d){const s=re(d);if(!(!s||!i.vehicle?.id)){h.value=s;try{const r=await Mt(i.vehicle.id,s);v("updated",r.data),await Q(),g.add({severity:r.cloudinary_warning?"warn":"success",summary:"تم الحذف",detail:r.message||r.cloudinary_warning||"تم حذف الصورة من المعرض",life:3e3})}catch(r){g.add({severity:"error",summary:"فشل الحذف",detail:r.response?.data?.message||"تعذر حذف الصورة",life:4e3})}finally{h.value=null}}}function ge(d){d&&(z.value=d,u.value=!0)}function oe(d){G.value||d===S.value||(G.value=!0,S.value=d,window.setTimeout(()=>{G.value=!1},120))}function Ke(){S.value>0&&oe(S.value-1)}function He(){S.value<U.value.length-1&&oe(S.value+1)}return(d,s)=>(a(),l("div",Yn,[q.value?(a(),l("div",Qn,[I(L(ie),{style:{width:"28px",height:"28px"}}),s[3]||(s[3]=n("span",null,"جاري تحميل الصور المحدّثة...",-1))])):N.value?(a(),l("div",Xn,[...s[4]||(s[4]=[n("i",{class:"pi pi-exclamation-triangle"},null,-1),n("span",null,"توكن API المعرض منتهي — راجع الإعدادات. تُعرض الصور المخزّنة إن وُجدت.",-1)])])):E.value?(a(),l("div",ea,[s[5]||(s[5]=n("i",{class:"pi pi-exclamation-circle"},null,-1)),n("span",null,m(x.value),1)])):j.value?(a(),l("div",ta,[s[6]||(s[6]=n("i",{class:"pi pi-check-circle"},null,-1)),o.value>0?(a(),l("span",na," تم حفظ "+m(o.value)+" صورة جديدة من API المعرض ",1)):(a(),l("span",aa,"صور محدّثة من API المعرض"))])):f("",!0),e.adminMode?(a(),l(R,{key:4},[s[13]||(s[13]=n("header",{class:"photos-section-header"},[n("h3",{class:"photos-section-title"},"إدارة الصور"),n("p",{class:"photos-section-sub"},"رفع صور جديدة للتاجر حسب المرحلة")],-1)),P.value?(a(),l("div",ia,[n("img",{src:P.value,alt:W.value,class:"preview-img",loading:"lazy",decoding:"async"},null,8,la),s[7]||(s[7]=n("span",{class:"preview-label"},"معاينة Vinstack",-1))])):f("",!0),(a(!0),l(R,null,H(L(At),r=>(a(),l("section",{key:r.key,class:Z(["stage-card",{"stage-card--dragover":A.value===r.key}]),onDragenter:te(c=>Ue(r.key),["prevent"]),onDragover:te(c=>Me(r.key),["prevent"]),onDragleave:c=>Oe(r.key,c),onDrop:te(c=>Ze(r.key,c),["prevent"])},[n("div",ra,[n("div",oa,[n("h4",da,m(r.label),1),n("div",ua,[n("span",ca,[s[8]||(s[8]=n("i",{class:"pi pi-cloud"},null,-1)),T(" Vinstack: "+m(me(r.key).vinstack),1)]),n("span",pa,[s[9]||(s[9]=n("i",{class:"pi pi-upload"},null,-1)),T(" مرفوعة من الإدارة: "+m(me(r.key).uploaded),1)])])])]),s[12]||(s[12]=n("div",{class:"stage-dropzone-hint"},[n("i",{class:"pi pi-cloud-upload"}),n("span",null,"اسحب الصور أو ملف ZIP هنا أو استخدم أزرار الرفع")],-1)),n("div",va,[n("input",{ref_for:!0,ref:c=>Ae(r.key,c),type:"file",accept:"image/jpeg,image/png,image/webp,image/gif",multiple:"",class:"file-input",onChange:c=>Fe(r.key,c)},null,40,fa),n("input",{ref_for:!0,ref:c=>Ee(r.key,c),type:"file",accept:".zip,application/zip,application/x-zip-compressed",class:"file-input",onChange:c=>je(r.key,c)},null,40,ma),I(L(F),{icon:"pi pi-upload",label:"رفع صور جديدة",loading:V.value===r.key,disabled:w.value===r.key,class:"upload-btn btn-add",onClick:c=>Re(r.key)},null,8,["loading","disabled","onClick"]),I(L(F),{icon:"pi pi-file-import",label:"رفع ملف مضغوط",severity:"secondary",loading:w.value===r.key,disabled:V.value===r.key,class:"upload-btn upload-btn--zip",onClick:c=>Te(r.key)},null,8,["loading","disabled","onClick"]),w.value===r.key?(a(),l("span",ha,[...s[10]||(s[10]=[n("i",{class:"pi pi-spin pi-spinner"},null,-1),T(" جاري رفع ZIP إلى Vinstack… ",-1)])])):f("",!0)]),se(r.key).length?(a(),l("div",ya,[(a(!0),l(R,null,H(se(r.key),c=>(a(),l("div",{key:c,class:Z(["thumb-card",{"thumb-card--uploaded":J(c)}])},[n("button",{type:"button",class:"thumb-btn",onClick:X=>ge(c)},[n("img",{src:c,alt:W.value,loading:"lazy",decoding:"async"},null,8,ba)],8,ga),J(c)?(a(),l("span",ka,"مرفوعة من الإدارة")):(a(),l("span",wa,"Vinstack")),J(c)?(a(),_(L(F),{key:2,icon:"pi pi-trash",severity:"danger",rounded:"",size:"small",class:"thumb-delete",loading:h.value===re(c),"aria-label":"حذف الصورة",onClick:X=>Ne(c)},null,8,["loading","onClick"])):f("",!0)],2))),128))])):(a(),l("p",$a,[...s[11]||(s[11]=[n("i",{class:"pi pi-image"},null,-1),T(" لا توجد صور — ارفع صوراً للتاجر ",-1)])]))],42,sa))),128)),I(ke,{visible:u.value,"onUpdate:visible":s[0]||(s[0]=r=>u.value=r),vehicle:$.value??e.vehicle,"start-url":z.value,"api-mode":e.apiMode},null,8,["visible","vehicle","start-url","api-mode"])],64)):e.compact?(a(),_(ze,{key:5,vehicle:$.value??e.vehicle,"api-mode":e.apiMode,"show-button":"",onGalleryUpdated:Ve},null,8,["vehicle","api-mode"])):(a(),l(R,{key:6},[s[20]||(s[20]=n("header",{class:"photos-section-header photos-section-header--dealer"},[n("h3",{class:"photos-section-title"},"صور السيارة")],-1)),P.value?(a(),l("div",xa,[n("img",{src:P.value,alt:W.value,class:"preview-img",loading:"lazy",decoding:"async"},null,8,Ca),s[14]||(s[14]=n("span",{class:"preview-label"},"معاينة",-1))])):f("",!0),U.value.length?(a(),l("div",La,[n("div",Ia,[n("button",{type:"button",class:"gallery-nav gallery-nav--prev",disabled:S.value===0,"aria-label":"الصورة السابقة",onClick:Ke},[...s[15]||(s[15]=[n("i",{class:"pi pi-chevron-right"},null,-1)])],8,Sa),n("button",{type:"button",class:"main-photo",onClick:s[1]||(s[1]=r=>ge(M.value))},[M.value?(a(),l("img",{key:M.value,src:M.value,alt:W.value,decoding:"async"},null,8,za)):f("",!0),J(M.value)?(a(),l("span",Da,"مرفوعة من الإدارة")):f("",!0),s[16]||(s[16]=n("span",{class:"zoom-hint"},[n("i",{class:"pi pi-search-plus"}),T(" تكبير")],-1))]),n("button",{type:"button",class:"gallery-nav gallery-nav--next",disabled:S.value>=U.value.length-1,"aria-label":"الصورة التالية",onClick:He},[...s[17]||(s[17]=[n("i",{class:"pi pi-chevron-left"},null,-1)])],8,Pa)]),U.value.length>1?(a(),l("div",Ba,[(a(!0),l(R,null,H(U.value,(r,c)=>(a(),l("button",{key:`${c}-${r}`,type:"button",class:Z(["gallery-thumb-btn",{active:c===S.value}]),onClick:X=>oe(c)},[n("img",{src:r,alt:`${W.value} thumbnail`,loading:"lazy",decoding:"async"},null,8,Va),J(r)?(a(),l("span",Aa)):f("",!0)],10,_a))),128))])):f("",!0),n("p",Ea,m(S.value+1)+" / "+m(U.value.length),1)])):P.value?(a(),l("div",Ra,[...s[18]||(s[18]=[n("i",{class:"pi pi-info-circle"},null,-1),n("span",null,"لا توجد صور عالية الدقة — المعاينة فقط متاحة",-1)])])):(a(),l("div",Ta,[...s[19]||(s[19]=[n("i",{class:"pi pi-image"},null,-1),n("span",null,"لا توجد صور لهذه السيارة",-1)])])),I(ke,{visible:u.value,"onUpdate:visible":s[2]||(s[2]=r=>u.value=r),vehicle:$.value,"start-url":z.value,"api-mode":e.apiMode},null,8,["visible","vehicle","start-url","api-mode"])],64))]))}},Ma=le(Ua,[["__scopeId","data-v-b225fc05"]]),Oa={key:0,class:"drawer-header"},Za={class:"drawer-header-top"},Fa={class:"drawer-title"},ja={class:"drawer-header-meta"},Na={key:0,class:"stale-note"},Ga={key:1,class:"drawer-header drawer-header--loading"},Ka={key:0,class:"drawer-loading"},Ha={key:1,class:"drawer-error"},qa={key:2,class:"drawer-body"},Wa={class:"section-title"},Ja={class:"field-grid"},Ya={key:0,class:"detail-section"},Qa={class:"field-grid"},Xa={key:1,class:"detail-section"},ei={key:0,class:"record-list"},ti={class:"record-title"},ni={key:0,class:"record-sub"},ai={key:1,class:"empty-section"},ii={class:"detail-section"},li={key:0,class:"record-list"},si=["href"],ri={key:1,class:"empty-section"},oi={__name:"VehicleDetailDrawer",props:{visible:{type:Boolean,default:!1},vehicleId:{type:[Number,String],default:null},mode:{type:String,default:"admin",validator:e=>["admin","dealer"].includes(e)}},emits:["update:visible"],setup(e,{emit:t}){const i=e,v=t,g=C(!1),p=C(null),u=C(null),z=b({get:()=>i.visible,set:o=>v("update:visible",o)}),V=b(()=>Pe(u.value?.status)),w=b(()=>i.mode==="admin"),h=b(()=>{const o=u.value??{};return{id:o.id??i.vehicleId,vin:o.vin,year:o.year,make:o.make,model:o.model,images:o.images??[],images_by_stage:o.images_by_stage,uploaded_images:o.uploaded_images??[],raw_data:{thumbnail_url:o.thumbnail_url,images:o.images,images_by_stage:o.images_by_stage,uploaded_images:o.uploaded_images}}});function B(o){!o||!u.value||(u.value.images=o.images??u.value.images,u.value.images_by_stage=o.images_by_stage??u.value.images_by_stage,u.value.uploaded_images=o.uploaded_images??u.value.uploaded_images,u.value.thumbnail_url=o.thumbnail_url??u.value.thumbnail_url)}const D=b(()=>`${i.mode==="dealer"?"/dealer/vehicles":"/admin/vehicles"}/${i.vehicleId}/details`);fe(()=>[i.visible,i.vehicleId],([o,k])=>{o&&k&&A(),o||(u.value=null,p.value=null)});async function A(){if(i.vehicleId){g.value=!0,p.value=null;try{const{data:o}=await Se.get(D.value);u.value=o.data}catch(o){p.value=o.response?.data?.message||"Failed to load vehicle details.",u.value=null}finally{g.value=!1}}}function S(){z.value=!1}function G(){u.value=null,p.value=null}function q(o){return xe(o)||"—"}function $(o){if(o.value===null||o.value===void 0||o.value==="")return"—";if(o.type==="date")return xe(o.value)||"—";if(o.type==="money"){const k=Number(o.value);if(Number.isFinite(k))return new Intl.NumberFormat(void 0,{style:"currency",currency:"USD",maximumFractionDigits:0}).format(k)}return String(o.value)}function j(o){return o.number||o.invoice_number||o.id||"Invoice"}function N(o){const k=[o.status,o.amount,o.date||o.created_at].filter(Boolean);return k.length?k.join(" · "):null}function E(o){return o.name||o.title||o.filename||o.type||"Document"}return(o,k)=>(a(),_(L(_e),{visible:z.value,"onUpdate:visible":k[0]||(k[0]=x=>z.value=x),position:"right",style:{width:"min(480px, 100vw)"},pt:{root:{class:"vehicle-detail-drawer"}},showCloseIcon:!1,onHide:G},{header:ee(()=>[u.value?(a(),l("div",Oa,[n("div",Za,[n("h2",Fa,m(u.value.title||"—"),1),I(L(F),{icon:"pi pi-times",text:"",rounded:"",severity:"secondary","aria-label":"Close",onClick:S})]),n("div",ja,[u.value.status?(a(),l("span",{key:0,class:Z(["status-pill",V.value])},[k[1]||(k[1]=n("span",{class:"status-dot"},null,-1)),T(" "+m(u.value.status),1)],2)):f("",!0),u.value.local_status?(a(),_(L(Be),{key:1,value:u.value.local_status,class:"local-tag"},null,8,["value"])):f("",!0)]),I(De,{vin:u.value.vin,class:"drawer-vin"},null,8,["vin"]),!u.value.vinstack_fresh&&!["manual","nujoom_al_jazeera"].includes(u.value.source)?(a(),l("div",Na,[...k[2]||(k[2]=[n("i",{class:"pi pi-info-circle"},null,-1),T(" Showing cached data — Vinstack live fetch unavailable. ",-1)])])):f("",!0)])):(a(),l("div",Ga,[I(L($e),{width:"70%",height:"1.4rem"}),I(L($e),{width:"40%",height:"1rem",class:"mt-sm"})]))]),default:ee(()=>[g.value?(a(),l("div",Ka,[I(L(ie),{style:{width:"36px",height:"36px"}})])):p.value?(a(),l("div",Ha,[k[3]||(k[3]=n("i",{class:"pi pi-exclamation-circle"},null,-1)),n("span",null,m(p.value),1),I(L(F),{label:"Retry",size:"small",outlined:"",onClick:A})])):u.value?(a(),l("div",qa,[I(Ma,{vehicle:h.value,"admin-mode":w.value,"api-mode":e.mode,onUpdated:B},null,8,["vehicle","admin-mode","api-mode"]),(a(!0),l(R,null,H(u.value.sections,x=>(a(),l("section",{key:x.key,class:"detail-section"},[n("h3",Wa,m(x.title),1),n("dl",Ja,[(a(!0),l(R,null,H(x.fields,P=>(a(),l(R,{key:`${x.key}-${P.key}`},[n("dt",null,m(P.label),1),n("dd",null,m($(P)),1)],64))),128))])]))),128)),u.value.assignment?.dealer_name?(a(),l("section",Ya,[k[6]||(k[6]=n("h3",{class:"section-title"},"Assignment",-1)),n("dl",Qa,[k[4]||(k[4]=n("dt",null,"Dealer",-1)),n("dd",null,m(u.value.assignment.dealer_name),1),k[5]||(k[5]=n("dt",null,"Assigned",-1)),n("dd",null,m(q(u.value.assignment.assigned_at)),1)])])):f("",!0),e.mode!=="dealer"?(a(),l("section",Xa,[k[7]||(k[7]=n("h3",{class:"section-title"},"Invoices",-1)),u.value.invoices?.length?(a(),l("div",ei,[(a(!0),l(R,null,H(u.value.invoices,(x,P)=>(a(),l("div",{key:x.id??P,class:"record-item"},[n("div",ti,m(j(x)),1),N(x)?(a(),l("div",ni,m(N(x)),1)):f("",!0)]))),128))])):(a(),l("p",ai,"—"))])):f("",!0),n("section",ii,[k[9]||(k[9]=n("h3",{class:"section-title"},"Documents",-1)),u.value.documents?.length?(a(),l("div",li,[(a(!0),l(R,null,H(u.value.documents,(x,P)=>(a(),l("a",{key:x.id??x.url??P,href:x.url||x.link||"#",class:"record-item record-link",target:"_blank",rel:"noopener noreferrer"},[k[8]||(k[8]=n("i",{class:"pi pi-file"},null,-1)),n("span",null,m(E(x)),1)],8,si))),128))])):(a(),l("p",ri,"—"))])])):f("",!0)]),_:1},8,["visible"]))}},hi=le(oi,[["__scopeId","data-v-b0ec30eb"]]);export{mi as V,hi as a,_e as b,Ma as c,dn as s};
