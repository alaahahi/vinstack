import{B as M,N as G,f as g,m as h,i as e,z as k,y as J,_ as X,u as Y,b as Z,A as ee,o as te,t as n,h as l,g as _,F,x as z,p as m,n as N,s as V,w as se,l as q,H as w,r as v,a8 as I,I as L}from"./app-BPvtm72T.js";import{s as ae}from"./index-Dl3QQbBV.js";import{s as le}from"./index-CezJAGrV.js";import{s as ie}from"./index-CF9Cv5Uj.js";import{s as ne}from"./index-GZvmG_ck.js";import{a as oe}from"./index-PqrkpA6a.js";import{a as de}from"./index-BMS2Lmel.js";import{s as U}from"./index-B23wtlfF.js";import{s as re}from"./index-BL0WAw6a.js";import{f as ce}from"./formatDateTime-BLTbgLs_.js";import"./index-stZqahdE.js";var ue=`
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
`,ge={root:{position:"relative"}},he={root:function(t){var r=t.instance,p=t.props;return["p-toggleswitch p-component",{"p-toggleswitch-checked":r.checked,"p-disabled":p.disabled,"p-invalid":r.$invalid}]},input:"p-toggleswitch-input",slider:"p-toggleswitch-slider",handle:"p-toggleswitch-handle"},_e=M.extend({name:"toggleswitch",style:ue,classes:he,inlineStyles:ge}),pe={name:"BaseToggleSwitch",extends:de,props:{trueValue:{type:null,default:!0},falseValue:{type:null,default:!1},readonly:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:_e,provide:function(){return{$pcToggleSwitch:this,$parentInstance:this}}},$={name:"ToggleSwitch",extends:pe,inheritAttrs:!1,emits:["change","focus","blur"],methods:{getPTOptions:function(t){var r=t==="root"?this.ptmi:this.ptm;return r(t,{context:{checked:this.checked,disabled:this.disabled}})},onChange:function(t){if(!this.disabled&&!this.readonly){var r=this.checked?this.falseValue:this.trueValue;this.writeValue(r,t),this.$emit("change",t)}},onFocus:function(t){this.$emit("focus",t)},onBlur:function(t){var r,p;this.$emit("blur",t),(r=(p=this.formField).onBlur)===null||r===void 0||r.call(p,t)}},computed:{checked:function(){return this.d_value===this.trueValue},dataP:function(){return G({checked:this.checked,disabled:this.disabled,invalid:this.$invalid})}}},fe=["data-p-checked","data-p-disabled","data-p"],ve=["id","checked","tabindex","disabled","readonly","aria-checked","aria-labelledby","aria-label","aria-invalid"],be=["data-p"],me=["data-p"];function we(o,t,r,p,c,d){return g(),h("div",k({class:o.cx("root"),style:o.sx("root")},d.getPTOptions("root"),{"data-p-checked":d.checked,"data-p-disabled":o.disabled,"data-p":d.dataP}),[e("input",k({id:o.inputId,type:"checkbox",role:"switch",class:[o.cx("input"),o.inputClass],style:o.inputStyle,checked:d.checked,tabindex:o.tabindex,disabled:o.disabled,readonly:o.readonly,"aria-checked":d.checked,"aria-labelledby":o.ariaLabelledby,"aria-label":o.ariaLabel,"aria-invalid":o.invalid||void 0,onFocus:t[0]||(t[0]=function(){return d.onFocus&&d.onFocus.apply(d,arguments)}),onBlur:t[1]||(t[1]=function(){return d.onBlur&&d.onBlur.apply(d,arguments)}),onChange:t[2]||(t[2]=function(){return d.onChange&&d.onChange.apply(d,arguments)})},d.getPTOptions("input")),null,16,ve),e("div",k({class:o.cx("slider")},d.getPTOptions("slider"),{"data-p":d.dataP}),[e("div",k({class:o.cx("handle")},d.getPTOptions("handle"),{"data-p":d.dataP}),[J(o.$slots,"handle",{checked:d.checked})],16,me)],16,be)],16,fe)}$.render=we;const ye={class:"admin-page dealer-notifications-page"},ke={class:"notifications-stack"},Ne={class:"admin-surface notif-card notif-card--wa"},Ve={class:"notif-card__head"},qe={class:"notif-card__titles"},Se={class:"vs-card-title"},Ce={class:"vs-card-subtitle"},Te={class:"notif-card__body"},Be={class:"field"},Pe={class:"field-hint"},xe={class:"field-grid"},Fe={class:"field"},ze={for:"wa-sender",class:"vs-form-label"},Le={class:"field field--toggle"},Ue={class:"vs-form-label"},$e={class:"toggle-wrap"},Oe={key:0,class:"events-panel"},Ae={class:"events-panel__head"},Ie={class:"events-panel__title"},je={class:"events-panel__sub"},De={class:"events-list"},Qe={class:"events-list__text"},Ee={key:2,class:"senders-list"},He={class:"senders-list__title"},Re={class:"senders-grid"},We={dir:"ltr"},Ke={class:"notif-card__footer"},Me={class:"admin-surface notif-card"},Ge={class:"notif-card__head"},Je={class:"notif-card__titles"},Xe={class:"vs-card-title"},Ye={class:"vs-card-subtitle"},Ze={class:"notif-card__body"},et={class:"field-grid field-grid--send"},tt={class:"field"},st={for:"dealer-select",class:"vs-form-label"},at={class:"dealer-option"},lt={class:"dealer-option__phone",dir:"ltr"},it={class:"send-all-toggle"},nt={key:0},ot={class:"field field--grow"},dt={for:"message-body",class:"vs-form-label"},rt={class:"notif-card__footer"},ct={class:"admin-surface notif-card"},ut={class:"notif-card__head"},gt={class:"notif-card__titles"},ht={class:"vs-card-title"},_t={class:"vs-card-subtitle"},pt={class:"notif-card__body notif-card__body--log"},ft={key:0,class:"log-loading"},vt={key:1,class:"log-empty"},bt={key:2,class:"log-list"},mt={class:"log-item__top"},wt={class:"log-item__message"},yt={class:"log-item__meta"},kt={key:0,class:"log-item__event"},Nt={dir:"ltr"},Vt={key:1},qt={__name:"NotificationsPage",setup(o){const{t}=Y(),r=Z(),p=v({configured:!1}),c=I({wa_queue_base_url:"",wa_queue_sender_id:null,wa_queue_enabled:!1,dealer_notification_events:{}}),d=v([]),u=I({dealer_id:null,message:"",send_to_all:!1}),S=v([]),b=v([]),f=v(null),C=v(!1),T=v(!1),B=v(!1),y=v(!1),P=L(()=>S.value.filter(i=>i.has_phone).length),O=L(()=>!p.value.configured||u.message.trim().length===0?!1:u.send_to_all?P.value>0:!!u.dealer_id),j=L(()=>u.send_to_all?t("dealerNotifications.sendToAllNow"):t("dealerNotifications.sendNow"));ee(()=>u.send_to_all,i=>{i&&(u.dealer_id=null)});function A(i){if(!i)return"";const s=`dealerNotifications.events.${i}`;return t(s)!==s?t(s):i}function D(i){return i.error_message?t("dealerNotifications.statusFailed"):i.wa_queue_status||t("dealerNotifications.statusQueued")}async function Q(){const{data:i}=await w.get("/admin/wa-queue/settings"),s=i.data??{};p.value=s,c.wa_queue_base_url=s.wa_queue_base_url??"",c.wa_queue_sender_id=s.wa_queue_sender_id??null,c.wa_queue_enabled=!!s.wa_queue_enabled,d.value=s.dealer_notification_event_catalog??[],c.dealer_notification_events={...s.dealer_notification_events??{}}}async function E(){const{data:i}=await w.get("/admin/dealer-notifications/dealers");S.value=i.data??[]}async function x(){y.value=!0;try{const{data:i}=await w.get("/admin/dealer-notifications");b.value=i.data??[]}finally{y.value=!1}}async function H(){C.value=!0;try{const{data:i}=await w.put("/admin/wa-queue/settings",{wa_queue_base_url:c.wa_queue_base_url||null,wa_queue_sender_id:c.wa_queue_sender_id||null,wa_queue_enabled:c.wa_queue_enabled,dealer_notification_events:c.dealer_notification_events});p.value=i.data??p.value,i.data?.dealer_notification_events&&(c.dealer_notification_events={...i.data.dealer_notification_events}),r.add({severity:"success",summary:i.message||t("dealerNotifications.saved"),life:3e3})}catch(i){r.add({severity:"error",summary:t("common.error"),detail:i.response?.data?.message||t("dealerNotifications.saveFailed"),life:4500})}finally{C.value=!1}}async function R(){T.value=!0,f.value=null;try{const{data:i}=await w.post("/admin/wa-queue/test-connection");f.value=i.data??{ok:!0,message:i.message},r.add({severity:f.value.ok?"success":"warn",summary:i.message,life:4e3})}catch(i){f.value=i.response?.data?.data??{ok:!1,message:i.response?.data?.message||t("dealerNotifications.testFailed")},r.add({severity:"error",summary:f.value.message,life:5e3})}finally{T.value=!1}}async function W(){if(O.value){B.value=!0;try{const i={message:u.message.trim(),send_to_all:u.send_to_all};u.send_to_all||(i.dealer_id=u.dealer_id);const{data:s}=await w.post("/admin/dealer-notifications/send",i);r.add({severity:s.failed>0&&s.sent>0?"warn":"success",summary:s.message,life:5e3}),u.message="",Array.isArray(s.data)&&s.data.length?b.value=[...s.data,...b.value]:s.data?b.value=[s.data,...b.value]:await x()}catch(i){const s=i.response?.data?.message||t("dealerNotifications.sendFailed");r.add({severity:"error",summary:s,detail:i.response?.data?.errors?Object.values(i.response.data.errors).flat().join(" · "):void 0,life:6e3})}finally{B.value=!1}}}return te(async()=>{await Promise.all([Q(),E(),x()])}),(i,s)=>(g(),h("div",ye,[e("div",ke,[e("section",Ne,[e("header",Ve,[s[6]||(s[6]=e("span",{class:"notif-card__icon notif-card__icon--wa"},[e("i",{class:"pi pi-whatsapp"})],-1)),e("div",qe,[e("h2",Se,n(l(t)("dealerNotifications.waQueueTitle")),1),e("p",Ce,n(l(t)("dealerNotifications.waQueueSub")),1)]),_(l(U),{class:"notif-card__badge",severity:p.value.configured?"success":"warn",value:p.value.configured?l(t)("dealerNotifications.configured"):l(t)("dealerNotifications.notConfigured")},null,8,["severity","value"])]),e("div",Te,[e("div",Be,[s[7]||(s[7]=e("label",{for:"wa-base",class:"vs-form-label"},"WA Queue Base URL",-1)),_(l(le),{id:"wa-base",modelValue:c.wa_queue_base_url,"onUpdate:modelValue":s[0]||(s[0]=a=>c.wa_queue_base_url=a),class:"w-full",dir:"ltr",placeholder:"https://tenant.wa-queue.test/api/v1"},null,8,["modelValue"]),e("small",Pe,n(l(t)("dealerNotifications.baseUrlHint")),1)]),e("div",xe,[e("div",Fe,[e("label",ze,n(l(t)("dealerNotifications.senderId")),1),_(l(ie),{id:"wa-sender",modelValue:c.wa_queue_sender_id,"onUpdate:modelValue":s[1]||(s[1]=a=>c.wa_queue_sender_id=a),class:"w-full","use-grouping":!1,"input-class":"w-full"},null,8,["modelValue"])]),e("div",Le,[e("label",Ue,n(l(t)("dealerNotifications.enable")),1),e("div",$e,[_(l($),{modelValue:c.wa_queue_enabled,"onUpdate:modelValue":s[2]||(s[2]=a=>c.wa_queue_enabled=a)},null,8,["modelValue"])])])]),d.value.length?(g(),h("div",Oe,[e("div",Ae,[e("h3",Ie,n(l(t)("dealerNotifications.eventsTitle")),1),e("p",je,n(l(t)("dealerNotifications.eventsSub")),1)]),e("ul",De,[(g(!0),h(F,null,z(d.value,a=>(g(),h("li",{key:a.key,class:"events-list__item"},[e("div",Qe,[e("strong",null,n(A(a.key)),1),e("small",null,n(a.key),1)]),_(l($),{modelValue:c.dealer_notification_events[a.key],"onUpdate:modelValue":K=>c.dealer_notification_events[a.key]=K},null,8,["modelValue","onUpdate:modelValue"])]))),128))])])):m("",!0),f.value?(g(),h("div",{key:1,class:N(["connection-result",f.value.ok?"connection-result--ok":"connection-result--error"])},[e("i",{class:N(["pi",f.value.ok?"pi-check-circle":"pi-times-circle"])},null,2),e("span",null,n(f.value.message),1)],2)):m("",!0),f.value?.senders?.length?(g(),h("div",Ee,[e("h3",He,n(l(t)("dealerNotifications.senders")),1),e("div",Re,[(g(!0),h(F,null,z(f.value.senders,a=>(g(),h("div",{key:a.id,class:N(["sender-card",{"sender-card--online":a.api_connected}])},[e("strong",null,n(a.name),1),e("span",We,n(a.phone),1),_(l(U),{severity:a.api_connected?"success":"danger",value:a.status_label||a.status},null,8,["severity","value"])],2))),128))])])):m("",!0)]),e("footer",Ke,[_(l(V),{label:l(t)("dealerNotifications.testConnection"),icon:"pi pi-bolt",severity:"secondary",outlined:"",loading:T.value,onClick:R},null,8,["label","loading"]),_(l(V),{label:l(t)("dealerNotifications.saveSettings"),icon:"pi pi-save",loading:C.value,onClick:H},null,8,["label","loading"])])]),e("section",Me,[e("header",Ge,[s[8]||(s[8]=e("span",{class:"notif-card__icon notif-card__icon--send"},[e("i",{class:"pi pi-send"})],-1)),e("div",Je,[e("h2",Xe,n(l(t)("dealerNotifications.sendTitle")),1),e("p",Ye,n(l(t)("dealerNotifications.sendSub")),1)])]),e("div",Ze,[e("div",et,[e("div",tt,[e("label",st,n(l(t)("dealerNotifications.selectDealer")),1),_(l(oe),{id:"dealer-select",modelValue:u.dealer_id,"onUpdate:modelValue":s[3]||(s[3]=a=>u.dealer_id=a),options:S.value,"option-label":"company_name","option-value":"id",placeholder:l(t)("dealerNotifications.selectDealerPlaceholder"),class:"w-full send-select",size:"small",filter:"",disabled:u.send_to_all},{option:se(({option:a})=>[e("div",at,[e("span",null,n(a.company_name),1),e("span",lt,n(a.phone||"—"),1)])]),_:1},8,["modelValue","options","placeholder","disabled"]),e("label",it,[_(l(ae),{modelValue:u.send_to_all,"onUpdate:modelValue":s[4]||(s[4]=a=>u.send_to_all=a),binary:"","input-id":"send-to-all"},null,8,["modelValue"]),e("span",null,[q(n(l(t)("dealerNotifications.sendToAll"))+" ",1),P.value?(g(),h("small",nt,"("+n(P.value)+")",1)):m("",!0)])])]),e("div",ot,[e("label",dt,n(l(t)("dealerNotifications.message")),1),_(l(ne),{id:"message-body",modelValue:u.message,"onUpdate:modelValue":s[5]||(s[5]=a=>u.message=a),rows:"4",class:"w-full",size:"small",placeholder:l(t)("dealerNotifications.messagePlaceholder"),"auto-resize":""},null,8,["modelValue","placeholder"])])])]),e("footer",rt,[_(l(V),{label:j.value,icon:"pi pi-whatsapp",size:"small",loading:B.value,disabled:!O.value,onClick:W},null,8,["label","loading","disabled"])])]),e("section",ct,[e("header",ut,[s[9]||(s[9]=e("span",{class:"notif-card__icon notif-card__icon--log"},[e("i",{class:"pi pi-list"})],-1)),e("div",gt,[e("h2",ht,n(l(t)("dealerNotifications.logTitle")),1),e("p",_t,n(l(t)("dealerNotifications.logSub")),1)]),_(l(V),{class:"notif-card__refresh",icon:"pi pi-refresh",text:"",rounded:"",loading:y.value,onClick:x},null,8,["loading"])]),e("div",pt,[y.value?(g(),h("div",ft,[_(l(re),{style:{width:"32px",height:"32px"}})])):b.value.length?(g(),h("ul",bt,[(g(!0),h(F,null,z(b.value,a=>(g(),h("li",{key:a.id,class:N(["log-item",{"log-item--failed":!a.success}])},[e("div",mt,[e("strong",null,n(a.dealer_name||l(t)("notifications.dealerFallback")),1),_(l(U),{severity:a.success?"success":"danger",value:D(a)},null,8,["severity","value"])]),e("p",wt,n(a.message),1),e("div",yt,[a.event?(g(),h("span",kt,n(A(a.event)),1)):m("",!0),e("span",Nt,[s[11]||(s[11]=e("i",{class:"pi pi-phone"},null,-1)),q(" "+n(a.phone),1)]),e("span",null,[s[12]||(s[12]=e("i",{class:"pi pi-clock"},null,-1)),q(" "+n(l(ce)(a.created_at)),1)]),a.author_name?(g(),h("span",Vt,[s[13]||(s[13]=e("i",{class:"pi pi-user"},null,-1)),q(" "+n(a.author_name),1)])):m("",!0)])],2))),128))])):(g(),h("div",vt,[s[10]||(s[10]=e("i",{class:"pi pi-inbox"},null,-1)),e("p",null,n(l(t)("dealerNotifications.logEmpty")),1)]))])])])]))}},Ot=X(qt,[["__scopeId","data-v-0fdb0dd3"]]);export{Ot as default};
