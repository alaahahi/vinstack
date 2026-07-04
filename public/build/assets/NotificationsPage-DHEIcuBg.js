import{B as A,N as E,o as h,l as p,g as e,y as v,x as H,u as R,b as G,k as M,t as d,f as s,e as u,n as y,m as B,F as x,q as L,s as k,w as W,G as w,r as f,a7 as O,H as J,j as C}from"./app-C3OzCEdq.js";import{s as K}from"./index-CI1ZzBSW.js";import{a as X,b as Y}from"./index-bQdAu3mY.js";import{s as Z}from"./index-CASCFfiz.js";import{a as ee}from"./index-efh_blTo.js";import{s as P}from"./index-C9fyzDvs.js";import{s as te}from"./index-B39CEaWn.js";import{f as se}from"./formatDateTime-BLTbgLs_.js";import{_ as ae}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./index-DRnPlycO.js";var ie=`
    .p-toggleswitch {
        display: inline-block;
        width: dt('toggleswitch.width');
        height: dt('toggleswitch.height');
    }

    .p-toggleswitch-input {
        cursor: pointer;
        appearance: none;
        position: absolute;
        top: 0;
        inset-inline-start: 0;
        width: 100%;
        height: 100%;
        padding: 0;
        margin: 0;
        opacity: 0;
        z-index: 1;
        outline: 0 none;
        border-radius: dt('toggleswitch.border.radius');
    }

    .p-toggleswitch-slider {
        cursor: pointer;
        width: 100%;
        height: 100%;
        border-width: dt('toggleswitch.border.width');
        border-style: solid;
        border-color: dt('toggleswitch.border.color');
        background: dt('toggleswitch.background');
        transition:
            background dt('toggleswitch.transition.duration'),
            color dt('toggleswitch.transition.duration'),
            border-color dt('toggleswitch.transition.duration'),
            outline-color dt('toggleswitch.transition.duration'),
            box-shadow dt('toggleswitch.transition.duration');
        border-radius: dt('toggleswitch.border.radius');
        outline-color: transparent;
        box-shadow: dt('toggleswitch.shadow');
    }

    .p-toggleswitch-handle {
        position: absolute;
        top: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        background: dt('toggleswitch.handle.background');
        color: dt('toggleswitch.handle.color');
        width: dt('toggleswitch.handle.size');
        height: dt('toggleswitch.handle.size');
        inset-inline-start: dt('toggleswitch.gap');
        margin-block-start: calc(-1 * calc(dt('toggleswitch.handle.size') / 2));
        border-radius: dt('toggleswitch.handle.border.radius');
        transition:
            background dt('toggleswitch.transition.duration'),
            color dt('toggleswitch.transition.duration'),
            inset-inline-start dt('toggleswitch.slide.duration'),
            box-shadow dt('toggleswitch.slide.duration');
    }

    .p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-slider {
        background: dt('toggleswitch.checked.background');
        border-color: dt('toggleswitch.checked.border.color');
    }

    .p-toggleswitch.p-toggleswitch-checked .p-toggleswitch-handle {
        background: dt('toggleswitch.handle.checked.background');
        color: dt('toggleswitch.handle.checked.color');
        inset-inline-start: calc(dt('toggleswitch.width') - calc(dt('toggleswitch.handle.size') + dt('toggleswitch.gap')));
    }

    .p-toggleswitch:not(.p-disabled):has(.p-toggleswitch-input:hover) .p-toggleswitch-slider {
        background: dt('toggleswitch.hover.background');
        border-color: dt('toggleswitch.hover.border.color');
    }

    .p-toggleswitch:not(.p-disabled):has(.p-toggleswitch-input:hover) .p-toggleswitch-handle {
        background: dt('toggleswitch.handle.hover.background');
        color: dt('toggleswitch.handle.hover.color');
    }

    .p-toggleswitch:not(.p-disabled):has(.p-toggleswitch-input:hover).p-toggleswitch-checked .p-toggleswitch-slider {
        background: dt('toggleswitch.checked.hover.background');
        border-color: dt('toggleswitch.checked.hover.border.color');
    }

    .p-toggleswitch:not(.p-disabled):has(.p-toggleswitch-input:hover).p-toggleswitch-checked .p-toggleswitch-handle {
        background: dt('toggleswitch.handle.checked.hover.background');
        color: dt('toggleswitch.handle.checked.hover.color');
    }

    .p-toggleswitch:not(.p-disabled):has(.p-toggleswitch-input:focus-visible) .p-toggleswitch-slider {
        box-shadow: dt('toggleswitch.focus.ring.shadow');
        outline: dt('toggleswitch.focus.ring.width') dt('toggleswitch.focus.ring.style') dt('toggleswitch.focus.ring.color');
        outline-offset: dt('toggleswitch.focus.ring.offset');
    }

    .p-toggleswitch.p-invalid > .p-toggleswitch-slider {
        border-color: dt('toggleswitch.invalid.border.color');
    }

    .p-toggleswitch.p-disabled {
        opacity: 1;
    }

    .p-toggleswitch.p-disabled .p-toggleswitch-slider {
        background: dt('toggleswitch.disabled.background');
    }

    .p-toggleswitch.p-disabled .p-toggleswitch-handle {
        background: dt('toggleswitch.handle.disabled.background');
    }
`,le={root:{position:"relative"}},oe={root:function(t){var r=t.instance,g=t.props;return["p-toggleswitch p-component",{"p-toggleswitch-checked":r.checked,"p-disabled":g.disabled,"p-invalid":r.$invalid}]},input:"p-toggleswitch-input",slider:"p-toggleswitch-slider",handle:"p-toggleswitch-handle"},ne=A.extend({name:"toggleswitch",style:ie,classes:oe,inlineStyles:le}),de={name:"BaseToggleSwitch",extends:ee,props:{trueValue:{type:null,default:!0},falseValue:{type:null,default:!1},readonly:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:ne,provide:function(){return{$pcToggleSwitch:this,$parentInstance:this}}},$={name:"ToggleSwitch",extends:de,inheritAttrs:!1,emits:["change","focus","blur"],methods:{getPTOptions:function(t){var r=t==="root"?this.ptmi:this.ptm;return r(t,{context:{checked:this.checked,disabled:this.disabled}})},onChange:function(t){if(!this.disabled&&!this.readonly){var r=this.checked?this.falseValue:this.trueValue;this.writeValue(r,t),this.$emit("change",t)}},onFocus:function(t){this.$emit("focus",t)},onBlur:function(t){var r,g;this.$emit("blur",t),(r=(g=this.formField).onBlur)===null||r===void 0||r.call(g,t)}},computed:{checked:function(){return this.d_value===this.trueValue},dataP:function(){return E({checked:this.checked,disabled:this.disabled,invalid:this.$invalid})}}},re=["data-p-checked","data-p-disabled","data-p"],ce=["id","checked","tabindex","disabled","readonly","aria-checked","aria-labelledby","aria-label","aria-invalid"],ue=["data-p"],ge=["data-p"];function he(n,t,r,g,c,l){return h(),p("div",v({class:n.cx("root"),style:n.sx("root")},l.getPTOptions("root"),{"data-p-checked":l.checked,"data-p-disabled":n.disabled,"data-p":l.dataP}),[e("input",v({id:n.inputId,type:"checkbox",role:"switch",class:[n.cx("input"),n.inputClass],style:n.inputStyle,checked:l.checked,tabindex:n.tabindex,disabled:n.disabled,readonly:n.readonly,"aria-checked":l.checked,"aria-labelledby":n.ariaLabelledby,"aria-label":n.ariaLabel,"aria-invalid":n.invalid||void 0,onFocus:t[0]||(t[0]=function(){return l.onFocus&&l.onFocus.apply(l,arguments)}),onBlur:t[1]||(t[1]=function(){return l.onBlur&&l.onBlur.apply(l,arguments)}),onChange:t[2]||(t[2]=function(){return l.onChange&&l.onChange.apply(l,arguments)})},l.getPTOptions("input")),null,16,ce),e("div",v({class:n.cx("slider")},l.getPTOptions("slider"),{"data-p":l.dataP}),[e("div",v({class:n.cx("handle")},l.getPTOptions("handle"),{"data-p":l.dataP}),[H(n.$slots,"handle",{checked:l.checked})],16,ge)],16,ue)],16,re)}$.render=he;const pe={class:"admin-page dealer-notifications-page"},_e={class:"notifications-stack"},fe={class:"admin-surface notif-card notif-card--wa"},we={class:"notif-card__head"},be={class:"notif-card__titles"},me={class:"vs-card-title"},ve={class:"vs-card-subtitle"},ye={class:"notif-card__body"},ke={class:"field"},Ne={class:"field-hint"},qe={class:"field-grid"},Ve={class:"field"},Se={for:"wa-sender",class:"vs-form-label"},Be={class:"field field--toggle"},Ce={class:"vs-form-label"},Pe={class:"toggle-wrap"},Fe={key:1,class:"senders-list"},Te={class:"senders-list__title"},xe={class:"senders-grid"},Le={dir:"ltr"},Oe={class:"notif-card__footer"},$e={class:"admin-surface notif-card"},ze={class:"notif-card__head"},Ue={class:"notif-card__titles"},je={class:"vs-card-title"},Ie={class:"vs-card-subtitle"},De={class:"notif-card__body"},Qe={class:"field-grid field-grid--send"},Ae={class:"field"},Ee={for:"dealer-select",class:"vs-form-label"},He={class:"dealer-option"},Re={class:"dealer-option__phone",dir:"ltr"},Ge={class:"field field--grow"},Me={for:"message-body",class:"vs-form-label"},We={class:"notif-card__footer"},Je={class:"admin-surface notif-card"},Ke={class:"notif-card__head"},Xe={class:"notif-card__titles"},Ye={class:"vs-card-title"},Ze={class:"vs-card-subtitle"},et={class:"notif-card__body notif-card__body--log"},tt={key:0,class:"log-loading"},st={key:1,class:"log-empty"},at={key:2,class:"log-list"},it={class:"log-item__top"},lt={class:"log-item__message"},ot={class:"log-item__meta"},nt={dir:"ltr"},dt={key:0},rt={__name:"NotificationsPage",setup(n){const{t}=R(),r=G(),g=f({configured:!1}),c=O({wa_queue_base_url:"",wa_queue_sender_id:null,wa_queue_enabled:!1}),l=O({dealer_id:null,message:""}),F=f([]),b=f([]),_=f(null),N=f(!1),q=f(!1),V=f(!1),m=f(!1),T=J(()=>!!l.dealer_id&&l.message.trim().length>0&&g.value.configured);function z(o){return o.error_message?t("dealerNotifications.statusFailed"):o.wa_queue_status||t("dealerNotifications.statusQueued")}async function U(){const{data:o}=await w.get("/admin/wa-queue/settings"),a=o.data??{};g.value=a,c.wa_queue_base_url=a.wa_queue_base_url??"",c.wa_queue_sender_id=a.wa_queue_sender_id??null,c.wa_queue_enabled=!!a.wa_queue_enabled}async function j(){const{data:o}=await w.get("/admin/dealer-notifications/dealers");F.value=o.data??[]}async function S(){m.value=!0;try{const{data:o}=await w.get("/admin/dealer-notifications");b.value=o.data??[]}finally{m.value=!1}}async function I(){N.value=!0;try{const{data:o}=await w.put("/admin/wa-queue/settings",{wa_queue_base_url:c.wa_queue_base_url||null,wa_queue_sender_id:c.wa_queue_sender_id||null,wa_queue_enabled:c.wa_queue_enabled});g.value=o.data??g.value,r.add({severity:"success",summary:o.message||t("dealerNotifications.saved"),life:3e3})}catch(o){r.add({severity:"error",summary:t("common.error"),detail:o.response?.data?.message||t("dealerNotifications.saveFailed"),life:4500})}finally{N.value=!1}}async function D(){q.value=!0,_.value=null;try{const{data:o}=await w.post("/admin/wa-queue/test-connection");_.value=o.data??{ok:!0,message:o.message},r.add({severity:_.value.ok?"success":"warn",summary:o.message,life:4e3})}catch(o){_.value=o.response?.data?.data??{ok:!1,message:o.response?.data?.message||t("dealerNotifications.testFailed")},r.add({severity:"error",summary:_.value.message,life:5e3})}finally{q.value=!1}}async function Q(){if(T.value){V.value=!0;try{const{data:o}=await w.post("/admin/dealer-notifications/send",{dealer_id:l.dealer_id,message:l.message.trim()});r.add({severity:"success",summary:o.message,life:4e3}),l.message="",o.data?b.value=[o.data,...b.value]:await S()}catch(o){const a=o.response?.data?.message||t("dealerNotifications.sendFailed");r.add({severity:"error",summary:a,detail:o.response?.data?.errors?Object.values(o.response.data.errors).flat().join(" · "):void 0,life:6e3})}finally{V.value=!1}}}return M(async()=>{await Promise.all([U(),j(),S()])}),(o,a)=>(h(),p("div",pe,[e("div",_e,[e("section",fe,[e("header",we,[a[5]||(a[5]=e("span",{class:"notif-card__icon notif-card__icon--wa"},[e("i",{class:"pi pi-whatsapp"})],-1)),e("div",be,[e("h2",me,d(s(t)("dealerNotifications.waQueueTitle")),1),e("p",ve,d(s(t)("dealerNotifications.waQueueSub")),1)]),u(s(P),{class:"notif-card__badge",severity:g.value.configured?"success":"warn",value:g.value.configured?s(t)("dealerNotifications.configured"):s(t)("dealerNotifications.notConfigured")},null,8,["severity","value"])]),e("div",ye,[e("div",ke,[a[6]||(a[6]=e("label",{for:"wa-base",class:"vs-form-label"},"WA Queue Base URL",-1)),u(s(K),{id:"wa-base",modelValue:c.wa_queue_base_url,"onUpdate:modelValue":a[0]||(a[0]=i=>c.wa_queue_base_url=i),class:"w-full",dir:"ltr",placeholder:"https://tenant.wa-queue.test/api/v1"},null,8,["modelValue"]),e("small",Ne,d(s(t)("dealerNotifications.baseUrlHint")),1)]),e("div",qe,[e("div",Ve,[e("label",Se,d(s(t)("dealerNotifications.senderId")),1),u(s(X),{id:"wa-sender",modelValue:c.wa_queue_sender_id,"onUpdate:modelValue":a[1]||(a[1]=i=>c.wa_queue_sender_id=i),class:"w-full","use-grouping":!1,"input-class":"w-full"},null,8,["modelValue"])]),e("div",Be,[e("label",Ce,d(s(t)("dealerNotifications.enable")),1),e("div",Pe,[u(s($),{modelValue:c.wa_queue_enabled,"onUpdate:modelValue":a[2]||(a[2]=i=>c.wa_queue_enabled=i)},null,8,["modelValue"])])])]),_.value?(h(),p("div",{key:0,class:y(["connection-result",_.value.ok?"connection-result--ok":"connection-result--error"])},[e("i",{class:y(["pi",_.value.ok?"pi-check-circle":"pi-times-circle"])},null,2),e("span",null,d(_.value.message),1)],2)):B("",!0),_.value?.senders?.length?(h(),p("div",Fe,[e("h3",Te,d(s(t)("dealerNotifications.senders")),1),e("div",xe,[(h(!0),p(x,null,L(_.value.senders,i=>(h(),p("div",{key:i.id,class:y(["sender-card",{"sender-card--online":i.api_connected}])},[e("strong",null,d(i.name),1),e("span",Le,d(i.phone),1),u(s(P),{severity:i.api_connected?"success":"danger",value:i.status_label||i.status},null,8,["severity","value"])],2))),128))])])):B("",!0)]),e("footer",Oe,[u(s(k),{label:s(t)("dealerNotifications.testConnection"),icon:"pi pi-bolt",severity:"secondary",outlined:"",loading:q.value,onClick:D},null,8,["label","loading"]),u(s(k),{label:s(t)("dealerNotifications.saveSettings"),icon:"pi pi-save",loading:N.value,onClick:I},null,8,["label","loading"])])]),e("section",$e,[e("header",ze,[a[7]||(a[7]=e("span",{class:"notif-card__icon notif-card__icon--send"},[e("i",{class:"pi pi-send"})],-1)),e("div",Ue,[e("h2",je,d(s(t)("dealerNotifications.sendTitle")),1),e("p",Ie,d(s(t)("dealerNotifications.sendSub")),1)])]),e("div",De,[e("div",Qe,[e("div",Ae,[e("label",Ee,d(s(t)("dealerNotifications.selectDealer")),1),u(s(Y),{id:"dealer-select",modelValue:l.dealer_id,"onUpdate:modelValue":a[3]||(a[3]=i=>l.dealer_id=i),options:F.value,"option-label":"company_name","option-value":"id",placeholder:s(t)("dealerNotifications.selectDealerPlaceholder"),class:"w-full",filter:""},{option:W(({option:i})=>[e("div",He,[e("span",null,d(i.company_name),1),e("span",Re,d(i.phone||"—"),1)])]),_:1},8,["modelValue","options","placeholder"])]),e("div",Ge,[e("label",Me,d(s(t)("dealerNotifications.message")),1),u(s(Z),{id:"message-body",modelValue:l.message,"onUpdate:modelValue":a[4]||(a[4]=i=>l.message=i),rows:"4",class:"w-full",placeholder:s(t)("dealerNotifications.messagePlaceholder"),"auto-resize":""},null,8,["modelValue","placeholder"])])])]),e("footer",We,[u(s(k),{label:s(t)("dealerNotifications.sendNow"),icon:"pi pi-whatsapp",loading:V.value,disabled:!T.value,onClick:Q},null,8,["label","loading","disabled"])])]),e("section",Je,[e("header",Ke,[a[8]||(a[8]=e("span",{class:"notif-card__icon notif-card__icon--log"},[e("i",{class:"pi pi-list"})],-1)),e("div",Xe,[e("h2",Ye,d(s(t)("dealerNotifications.logTitle")),1),e("p",Ze,d(s(t)("dealerNotifications.logSub")),1)]),u(s(k),{class:"notif-card__refresh",icon:"pi pi-refresh",text:"",rounded:"",loading:m.value,onClick:S},null,8,["loading"])]),e("div",et,[m.value?(h(),p("div",tt,[u(s(te),{style:{width:"32px",height:"32px"}})])):b.value.length?(h(),p("ul",at,[(h(!0),p(x,null,L(b.value,i=>(h(),p("li",{key:i.id,class:y(["log-item",{"log-item--failed":!i.success}])},[e("div",it,[e("strong",null,d(i.dealer_name||s(t)("notifications.dealerFallback")),1),u(s(P),{severity:i.success?"success":"danger",value:z(i)},null,8,["severity","value"])]),e("p",lt,d(i.message),1),e("div",ot,[e("span",nt,[a[10]||(a[10]=e("i",{class:"pi pi-phone"},null,-1)),C(" "+d(i.phone),1)]),e("span",null,[a[11]||(a[11]=e("i",{class:"pi pi-clock"},null,-1)),C(" "+d(s(se)(i.created_at)),1)]),i.author_name?(h(),p("span",dt,[a[12]||(a[12]=e("i",{class:"pi pi-user"},null,-1)),C(" "+d(i.author_name),1)])):B("",!0)])],2))),128))])):(h(),p("div",st,[a[9]||(a[9]=e("i",{class:"pi pi-inbox"},null,-1)),e("p",null,d(s(t)("dealerNotifications.logEmpty")),1)]))])])])]))}},vt=ae(rt,[["__scopeId","data-v-ae11dc81"]]);export{vt as default};
