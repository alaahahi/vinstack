import{B as M,N as A,x as U,o as s,k as d,a as q,y as G,c as J,w as Q,f as c,t as b,e as u,d as m,l as h,F as W,p as X,s as C,a6 as Y,g as Z,E as ee,r as v,G as T,H as w,z as te,n as $}from"./app-CcxeGkay.js";import{s as ae}from"./index-DWj8al8P.js";import{s as ie}from"./index-BBdOmMrI.js";import{c as ne,V as re}from"./vehicleMeta-DnjG4q11.js";import{f as le}from"./formatDateTime-BLTbgLs_.js";import{_ as oe}from"./_plugin-vue_export-helper-DlAUqK2U.js";var se=`
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
`,de={root:function(t){var a=t.instance,n=t.props;return["p-textarea p-component",{"p-filled":a.$filled,"p-textarea-resizable ":n.autoResize,"p-textarea-sm p-inputfield-sm":n.size==="small","p-textarea-lg p-inputfield-lg":n.size==="large","p-invalid":a.$invalid,"p-variant-filled":a.$variant==="filled","p-textarea-fluid":a.$fluid}]}},ce=M.extend({name:"textarea",style:se,classes:de}),ue={name:"BaseTextarea",extends:ae,props:{autoResize:Boolean},style:ce,provide:function(){return{$pcTextarea:this,$parentInstance:this}}};function x(e){"@babel/helpers - typeof";return x=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},x(e)}function he(e,t,a){return(t=ve(t))in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function ve(e){var t=pe(e,"string");return x(t)=="symbol"?t:t+""}function pe(e,t){if(x(e)!="object"||!e)return e;var a=e[Symbol.toPrimitive];if(a!==void 0){var n=a.call(e,t);if(x(n)!="object")return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var j={name:"Textarea",extends:ue,inheritAttrs:!1,observer:null,mounted:function(){var t=this;this.autoResize&&(this.observer=new ResizeObserver(function(){requestAnimationFrame(function(){t.resize()})}),this.observer.observe(this.$el))},updated:function(){this.autoResize&&this.resize()},beforeUnmount:function(){this.observer&&this.observer.disconnect()},methods:{resize:function(){if(this.$el.offsetParent){var t=this.$el.style.height,a=parseInt(t)||0,n=this.$el.scrollHeight,y=!a||n>a,l=a&&n<a;l?(this.$el.style.height="auto",this.$el.style.height="".concat(this.$el.scrollHeight,"px")):y&&(this.$el.style.height="".concat(n,"px"))}},onInput:function(t){this.autoResize&&this.resize(),this.writeValue(t.target.value,t)}},computed:{attrs:function(){return U(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return A(he({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},fe=["value","name","disabled","aria-invalid","data-p"];function _e(e,t,a,n,y,l){return s(),d("textarea",U({class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.invalid||void 0,"data-p":l.dataP,onInput:t[0]||(t[0]=function(){return l.onInput&&l.onInput.apply(l,arguments)})},l.attrs),null,16,fe)}j.render=_e;const be={key:0,class:"vehicle-chat"},me={class:"vehicle-chat__header"},ye={class:"vehicle-chat__title"},ge={key:0,class:"vehicle-chat__loading"},xe={key:1,class:"vehicle-chat__empty"},ke={class:"vehicle-chat__bubble-wrap"},we={key:0,class:"vehicle-chat__sender"},$e={key:1,class:"vehicle-chat__text"},ze=["href"],Pe=["src"],Se={class:"vehicle-chat__time",dir:"ltr"},Ce={class:"vehicle-chat__composer"},Te={key:0,class:"vehicle-chat__pending"},Ve=["src"],Be={class:"vehicle-chat__composer-row"},Re={__name:"VehicleChatDialog",props:{visible:{type:Boolean,default:!1},vehicle:{type:Object,default:null},mode:{type:String,default:"dealer",validator:e=>["admin","dealer"].includes(e)}},emits:["update:visible","read","sent"],setup(e,{emit:t}){const a=e,n=t,y=q(),l=v([]),z=v(!1),f=v(!1),p=v(""),g=v(null),_=v(null),k=v(null),V=v(null),B=w({get:()=>a.visible,set:r=>n("update:visible",r)}),P=w(()=>a.mode==="dealer"?"/dealer":"/admin"),E=w(()=>a.mode==="dealer"?"محادثة السيارة":"محادثة مع التاجر"),R=w(()=>f.value?!1:p.value.trim()!==""||!!g.value);async function L(){await O(),await D()}function I(){p.value="",S(),l.value=[]}async function O(){if(a.vehicle?.id){z.value=!0;try{const{data:r}=await T.get(`${P.value}/vehicles/${a.vehicle.id}/messages`);l.value=r.data??[],await F()}catch(r){y.add({severity:"error",summary:"خطأ",detail:r.response?.data?.message||"تعذّر تحميل المحادثة",life:4e3})}finally{z.value=!1}}}async function D(){if(a.vehicle?.id)try{await T.post(`${P.value}/vehicles/${a.vehicle.id}/messages/read`),n("read",a.vehicle)}catch{}}async function F(){await te(),k.value&&(k.value.scrollTop=k.value.scrollHeight)}function N(){V.value?.click()}function K(r){const o=r.target.files?.[0];o&&(g.value=o,_.value=URL.createObjectURL(o),r.target.value="")}function S(){_.value&&URL.revokeObjectURL(_.value),g.value=null,_.value=null}async function H(){if(!R.value||!a.vehicle?.id)return;f.value=!0;const r=new FormData;p.value.trim()&&r.append("body",p.value.trim()),g.value&&r.append("image",g.value);try{const{data:o}=await T.post(`${P.value}/vehicles/${a.vehicle.id}/messages`,r,{headers:{"Content-Type":"multipart/form-data"}});l.value.push(o.data),p.value="",S(),await F(),n("sent",a.vehicle)}catch(o){y.add({severity:"error",summary:"خطأ",detail:o.response?.data?.message||o.response?.data?.errors?.body?.[0]||"فشل إرسال الرسالة",life:4e3})}finally{f.value=!1}}return G(()=>a.visible,r=>{r||I()}),(r,o)=>(s(),J(u(ee),{visible:B.value,"onUpdate:visible":o[1]||(o[1]=i=>B.value=i),header:E.value,modal:"",class:"vehicle-chat-dialog",style:{width:"min(560px, 100vw)"},onShow:L,onHide:I},{default:Q(()=>[e.vehicle?(s(),d("div",be,[c("div",me,[c("div",ye,b(u(ne)(e.vehicle)),1),m(re,{vin:e.vehicle.vin,block:""},null,8,["vin"])]),c("div",{ref_key:"scrollEl",ref:k,class:"vehicle-chat__messages"},[z.value?(s(),d("div",ge,[m(u(ie),{style:{width:"28px",height:"28px"},"stroke-width":"4"})])):l.value.length?h("",!0):(s(),d("div",xe," ابدأ المحادثة بإرسال رسالة أو صورة. ")),(s(!0),d(W,null,X(l.value,i=>(s(),d("div",{key:i.id,class:$(["vehicle-chat__row",i.is_mine?"vehicle-chat__row--mine":"vehicle-chat__row--theirs"])},[i.is_mine?h("",!0):(s(),d("div",{key:0,class:$(["vehicle-chat__avatar",i.author_role==="dealer"?"vehicle-chat__avatar--dealer":"vehicle-chat__avatar--admin"])},b(i.author_initial),3)),c("div",ke,[c("div",{class:$(["vehicle-chat__bubble",i.author_role==="dealer"?"vehicle-chat__bubble--dealer":"vehicle-chat__bubble--admin"])},[i.is_mine?h("",!0):(s(),d("div",we,b(i.author_name),1)),i.body?(s(),d("p",$e,b(i.body),1)):h("",!0),i.attachment_url?(s(),d("a",{key:2,href:i.attachment_url,target:"_blank",rel:"noopener noreferrer",class:"vehicle-chat__image-link"},[c("img",{src:i.attachment_url,alt:"مرفق",class:"vehicle-chat__image",loading:"lazy"},null,8,Pe)],8,ze)):h("",!0)],2),c("div",Se,b(u(le)(i.created_at)),1)]),i.is_mine?(s(),d("div",{key:1,class:$(["vehicle-chat__avatar",i.author_role==="dealer"?"vehicle-chat__avatar--dealer":"vehicle-chat__avatar--admin"])},b(i.author_initial),3)):h("",!0)],2))),128))],512),c("div",Ce,[c("input",{ref_key:"fileInput",ref:V,type:"file",accept:"image/*",class:"vehicle-chat__file-input",onChange:K},null,544),_.value?(s(),d("div",Te,[c("img",{src:_.value,alt:"معاينة",class:"vehicle-chat__pending-img"},null,8,Ve),m(u(C),{icon:"pi pi-times",text:"",rounded:"",severity:"secondary",onClick:S})])):h("",!0),c("div",Be,[m(u(C),{icon:"pi pi-image",severity:"secondary",text:"",rounded:"","aria-label":"إرفاق صورة",disabled:f.value,onClick:N},null,8,["disabled"]),m(u(j),{modelValue:p.value,"onUpdate:modelValue":o[0]||(o[0]=i=>p.value=i),rows:"2","auto-resize":"",class:"vehicle-chat__input",placeholder:"اكتب رسالتك...",disabled:f.value,onKeydown:Y(Z(H,["exact","prevent"]),["enter"])},null,8,["modelValue","disabled","onKeydown"]),m(u(C),{icon:"pi pi-send",class:"btn-cta vehicle-chat__send",loading:f.value,disabled:!R.value,"aria-label":"إرسال",onClick:H},null,8,["loading","disabled"])])])])):h("",!0)]),_:1},8,["visible","header"]))}},Le=oe(Re,[["__scopeId","data-v-a6acd6c2"]]);export{Le as V,j as s};
