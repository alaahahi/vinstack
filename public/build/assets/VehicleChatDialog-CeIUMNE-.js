import{B as G,N as J,y as j,o as d,l as c,u as Q,al as W,b as X,z as Y,c as Z,w as ee,g as h,t as m,f as l,e as y,m as v,F as te,q as ae,s as V,a6 as ie,h as ne,E as re,H as k,r as p,n as w,G as I,A as le}from"./app-CJThrM21.js";import{s as oe}from"./index-CXkPwVca.js";import{s as se}from"./index-CQj9TL3c.js";import{c as de,V as ce}from"./vehicleMeta-C1TRUEtY.js";import{f as ue}from"./formatDateTime-BLTbgLs_.js";import{_ as he}from"./_plugin-vue_export-helper-DlAUqK2U.js";var ve=`
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
`,pe={root:function(t){var a=t.instance,o=t.props;return["p-textarea p-component",{"p-filled":a.$filled,"p-textarea-resizable ":o.autoResize,"p-textarea-sm p-inputfield-sm":o.size==="small","p-textarea-lg p-inputfield-lg":o.size==="large","p-invalid":a.$invalid,"p-variant-filled":a.$variant==="filled","p-textarea-fluid":a.$fluid}]}},fe=G.extend({name:"textarea",style:ve,classes:pe}),me={name:"BaseTextarea",extends:oe,props:{autoResize:Boolean},style:fe,provide:function(){return{$pcTextarea:this,$parentInstance:this}}};function $(e){"@babel/helpers - typeof";return $=typeof Symbol=="function"&&typeof Symbol.iterator=="symbol"?function(t){return typeof t}:function(t){return t&&typeof Symbol=="function"&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},$(e)}function _e(e,t,a){return(t=be(t))in e?Object.defineProperty(e,t,{value:a,enumerable:!0,configurable:!0,writable:!0}):e[t]=a,e}function be(e){var t=ye(e,"string");return $(t)=="symbol"?t:t+""}function ye(e,t){if($(e)!="object"||!e)return e;var a=e[Symbol.toPrimitive];if(a!==void 0){var o=a.call(e,t);if($(o)!="object")return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return(t==="string"?String:Number)(e)}var E={name:"Textarea",extends:me,inheritAttrs:!1,observer:null,mounted:function(){var t=this;this.autoResize&&(this.observer=new ResizeObserver(function(){requestAnimationFrame(function(){t.resize()})}),this.observer.observe(this.$el))},updated:function(){this.autoResize&&this.resize()},beforeUnmount:function(){this.observer&&this.observer.disconnect()},methods:{resize:function(){if(this.$el.offsetParent){var t=this.$el.style.height,a=parseInt(t)||0,o=this.$el.scrollHeight,n=!a||o>a,u=a&&o<a;u?(this.$el.style.height="auto",this.$el.style.height="".concat(this.$el.scrollHeight,"px")):n&&(this.$el.style.height="".concat(o,"px"))}},onInput:function(t){this.autoResize&&this.resize(),this.writeValue(t.target.value,t)}},computed:{attrs:function(){return j(this.ptmi("root",{context:{filled:this.$filled,disabled:this.disabled}}),this.formField)},dataP:function(){return J(_e({invalid:this.$invalid,fluid:this.$fluid,filled:this.$variant==="filled"},this.size,this.size))}}},ge=["value","name","disabled","aria-invalid","data-p"];function xe(e,t,a,o,n,u){return d(),c("textarea",j({class:e.cx("root"),value:e.d_value,name:e.name,disabled:e.disabled,"aria-invalid":e.invalid||void 0,"data-p":u.dataP,onInput:t[0]||(t[0]=function(){return u.onInput&&u.onInput.apply(u,arguments)})},u.attrs),null,16,ge)}E.render=xe;const ke={key:0,class:"vehicle-chat"},we=["dir"],$e={class:"vehicle-chat__title"},ze={key:0,class:"vehicle-chat__loading"},Se=["dir"],Pe={class:"vehicle-chat__bubble-wrap"},Ce=["dir"],Te={key:0,class:"vehicle-chat__sender"},Ve={key:1,class:"vehicle-chat__text"},Ie=["href"],Re=["src","alt"],Be=["dir"],Fe={key:0,class:"vehicle-chat__pending"},He=["src","alt"],Ue={class:"vehicle-chat__composer-box"},De={__name:"VehicleChatDialog",props:{visible:{type:Boolean,default:!1},vehicle:{type:Object,default:null},mode:{type:String,default:"dealer",validator:e=>["admin","dealer"].includes(e)}},emits:["update:visible","read","sent"],setup(e,{emit:t}){const{t:a}=Q(),o=W(),n=e,u=t,R=X(),g=p([]),P=p(!1),_=p(!1),f=p(""),x=p(null),b=p(null),z=p(null),B=p(null),F=k({get:()=>n.visible,set:r=>u("update:visible",r)}),C=k(()=>n.mode==="dealer"?"/dealer":"/admin"),S=k(()=>o.isRtl?"rtl":"ltr"),O=k(()=>n.mode==="dealer"?a("chat.titleDealer"):a("chat.titleAdmin")),H=k(()=>_.value?!1:f.value.trim()!==""||!!x.value);async function N(){await A(),await K()}function U(){f.value="",T(),g.value=[]}async function A(){if(n.vehicle?.id){P.value=!0;try{const{data:r}=await I.get(`${C.value}/vehicles/${n.vehicle.id}/messages`);g.value=r.data??[],await D()}catch(r){R.add({severity:"error",summary:a("common.error"),detail:r.response?.data?.message||a("chat.loadFailed"),life:4e3})}finally{P.value=!1}}}async function K(){if(n.vehicle?.id)try{await I.post(`${C.value}/vehicles/${n.vehicle.id}/messages/read`),u("read",n.vehicle)}catch{}}async function D(){await le(),z.value&&(z.value.scrollTop=z.value.scrollHeight)}function M(){B.value?.click()}function q(r){const s=r.target.files?.[0];s&&(x.value=s,b.value=URL.createObjectURL(s),r.target.value="")}function T(){b.value&&URL.revokeObjectURL(b.value),x.value=null,b.value=null}async function L(){if(!H.value||!n.vehicle?.id)return;_.value=!0;const r=new FormData;f.value.trim()&&r.append("body",f.value.trim()),x.value&&r.append("image",x.value);try{const{data:s}=await I.post(`${C.value}/vehicles/${n.vehicle.id}/messages`,r,{headers:{"Content-Type":"multipart/form-data"}});g.value.push(s.data),f.value="",T(),await D(),u("sent",n.vehicle)}catch(s){R.add({severity:"error",summary:a("common.error"),detail:s.response?.data?.message||s.response?.data?.errors?.body?.[0]||a("chat.sendFailed"),life:4e3})}finally{_.value=!1}}return Y(()=>n.visible,r=>{r||U()}),(r,s)=>(d(),Z(l(re),{visible:F.value,"onUpdate:visible":s[1]||(s[1]=i=>F.value=i),header:O.value,modal:"",class:"vehicle-chat-dialog",style:{width:"min(680px, 94vw)"},"content-style":{padding:"0 1rem 1rem"},onShow:N,onHide:U},{default:ee(()=>[e.vehicle?(d(),c("div",ke,[h("div",{class:"vehicle-chat__header",dir:S.value},[h("div",$e,m(l(de)(e.vehicle)),1),y(ce,{vin:e.vehicle.vin,block:""},null,8,["vin"])],8,we),h("div",{ref_key:"scrollEl",ref:z,class:"vehicle-chat__messages",dir:"ltr"},[P.value?(d(),c("div",ze,[y(l(se),{style:{width:"28px",height:"28px"},"stroke-width":"4"})])):g.value.length?v("",!0):(d(),c("div",{key:1,class:"vehicle-chat__empty",dir:S.value},m(l(a)("chat.empty")),9,Se)),(d(!0),c(te,null,ae(g.value,i=>(d(),c("div",{key:i.id,class:w(["vehicle-chat__row",i.is_mine?"vehicle-chat__row--mine":"vehicle-chat__row--theirs"])},[i.is_mine?v("",!0):(d(),c("div",{key:0,class:w(["vehicle-chat__avatar",i.author_role==="dealer"?"vehicle-chat__avatar--dealer":"vehicle-chat__avatar--admin"])},m(i.author_initial),3)),h("div",Pe,[h("div",{class:w(["vehicle-chat__bubble",i.author_role==="dealer"?"vehicle-chat__bubble--dealer":"vehicle-chat__bubble--admin"]),dir:S.value},[i.is_mine?v("",!0):(d(),c("div",Te,m(i.author_name),1)),i.body?(d(),c("p",Ve,m(i.body),1)):v("",!0),i.attachment_url?(d(),c("a",{key:2,href:i.attachment_url,target:"_blank",rel:"noopener noreferrer",class:"vehicle-chat__image-link"},[h("img",{src:i.attachment_url,alt:l(a)("chat.attachment"),class:"vehicle-chat__image",loading:"lazy"},null,8,Re)],8,Ie)):v("",!0)],10,Ce),h("div",{class:w(["vehicle-chat__time",i.is_mine?"vehicle-chat__time--mine":"vehicle-chat__time--theirs"]),dir:"ltr"},m(l(ue)(i.created_at)),3)]),i.is_mine?(d(),c("div",{key:1,class:w(["vehicle-chat__avatar",i.author_role==="dealer"?"vehicle-chat__avatar--dealer":"vehicle-chat__avatar--admin"])},m(i.author_initial),3)):v("",!0)],2))),128))],512),h("div",{class:"vehicle-chat__composer",dir:S.value},[h("input",{ref_key:"fileInput",ref:B,type:"file",accept:"image/*",class:"vehicle-chat__file-input",onChange:q},null,544),b.value?(d(),c("div",Fe,[h("img",{src:b.value,alt:l(a)("chat.preview"),class:"vehicle-chat__pending-img"},null,8,He),y(l(V),{icon:"pi pi-times",text:"",rounded:"",severity:"secondary",onClick:T})])):v("",!0),h("div",Ue,[y(l(V),{icon:"pi pi-image",severity:"secondary",text:"",rounded:"",class:"vehicle-chat__attach","aria-label":l(a)("chat.attachImage"),disabled:_.value,onClick:M},null,8,["aria-label","disabled"]),y(l(E),{modelValue:f.value,"onUpdate:modelValue":s[0]||(s[0]=i=>f.value=i),rows:"2","auto-resize":"",class:"vehicle-chat__input",placeholder:l(a)("chat.placeholder"),disabled:_.value,onKeydown:ie(ne(L,["exact","prevent"]),["enter"])},null,8,["modelValue","placeholder","disabled","onKeydown"]),y(l(V),{icon:"pi pi-send",class:"btn-cta vehicle-chat__send",loading:_.value,disabled:!H.value,"aria-label":l(a)("chat.send"),onClick:L},null,8,["loading","disabled","aria-label"])])],8,Be)])):v("",!0)]),_:1},8,["visible","header"]))}},Ke=he(De,[["__scopeId","data-v-9034139a"]]);export{Ke as V,E as s};
