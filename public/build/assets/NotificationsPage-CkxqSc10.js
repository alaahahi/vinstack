import{B as R,N as W,f as g,m as h,i as e,z as v,y as M,_ as G,u as J,b as K,A as X,o as Y,t as d,h as l,g as c,n as y,p as k,F as L,x as O,s as N,w as Z,l as V,H as w,r as f,a8 as $,I as F}from"./app-cSfsfD7z.js";import{s as ee}from"./index-lXKKMO5e.js";import{s as te}from"./index-DRpuIzj5.js";import{s as se}from"./index-D6d1B8cY.js";import{s as ae}from"./index-CicLEdwC.js";import{a as le}from"./index-C_KCi_lc.js";import{a as ie}from"./index-BpV9SGOQ.js";import{s as x}from"./index-p9YOWoEG.js";import{s as ne}from"./index-DKDtdGqT.js";import{f as oe}from"./formatDateTime-BLTbgLs_.js";import"./index-B90B8FIJ.js";var de=`
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
`,re={root:{position:"relative"}},ce={root:function(t){var r=t.instance,p=t.props;return["p-toggleswitch p-component",{"p-toggleswitch-checked":r.checked,"p-disabled":p.disabled,"p-invalid":r.$invalid}]},input:"p-toggleswitch-input",slider:"p-toggleswitch-slider",handle:"p-toggleswitch-handle"},ue=R.extend({name:"toggleswitch",style:de,classes:ce,inlineStyles:re}),ge={name:"BaseToggleSwitch",extends:ie,props:{trueValue:{type:null,default:!0},falseValue:{type:null,default:!1},readonly:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:ue,provide:function(){return{$pcToggleSwitch:this,$parentInstance:this}}},U={name:"ToggleSwitch",extends:ge,inheritAttrs:!1,emits:["change","focus","blur"],methods:{getPTOptions:function(t){var r=t==="root"?this.ptmi:this.ptm;return r(t,{context:{checked:this.checked,disabled:this.disabled}})},onChange:function(t){if(!this.disabled&&!this.readonly){var r=this.checked?this.falseValue:this.trueValue;this.writeValue(r,t),this.$emit("change",t)}},onFocus:function(t){this.$emit("focus",t)},onBlur:function(t){var r,p;this.$emit("blur",t),(r=(p=this.formField).onBlur)===null||r===void 0||r.call(p,t)}},computed:{checked:function(){return this.d_value===this.trueValue},dataP:function(){return W({checked:this.checked,disabled:this.disabled,invalid:this.$invalid})}}},he=["data-p-checked","data-p-disabled","data-p"],pe=["id","checked","tabindex","disabled","readonly","aria-checked","aria-labelledby","aria-label","aria-invalid"],_e=["data-p"],fe=["data-p"];function be(o,t,r,p,u,a){return g(),h("div",v({class:o.cx("root"),style:o.sx("root")},a.getPTOptions("root"),{"data-p-checked":a.checked,"data-p-disabled":o.disabled,"data-p":a.dataP}),[e("input",v({id:o.inputId,type:"checkbox",role:"switch",class:[o.cx("input"),o.inputClass],style:o.inputStyle,checked:a.checked,tabindex:o.tabindex,disabled:o.disabled,readonly:o.readonly,"aria-checked":a.checked,"aria-labelledby":o.ariaLabelledby,"aria-label":o.ariaLabel,"aria-invalid":o.invalid||void 0,onFocus:t[0]||(t[0]=function(){return a.onFocus&&a.onFocus.apply(a,arguments)}),onBlur:t[1]||(t[1]=function(){return a.onBlur&&a.onBlur.apply(a,arguments)}),onChange:t[2]||(t[2]=function(){return a.onChange&&a.onChange.apply(a,arguments)})},a.getPTOptions("input")),null,16,pe),e("div",v({class:o.cx("slider")},a.getPTOptions("slider"),{"data-p":a.dataP}),[e("div",v({class:o.cx("handle")},a.getPTOptions("handle"),{"data-p":a.dataP}),[M(o.$slots,"handle",{checked:a.checked})],16,fe)],16,_e)],16,he)}U.render=be;const we={class:"admin-page dealer-notifications-page"},me={class:"notifications-stack"},ve={class:"admin-surface notif-card notif-card--wa"},ye={class:"notif-card__head"},ke={class:"notif-card__titles"},Ne={class:"vs-card-title"},Ve={class:"vs-card-subtitle"},qe={class:"notif-card__body"},Se={class:"field"},Be={class:"field-hint"},Ce={class:"field-grid"},Pe={class:"field"},Te={for:"wa-sender",class:"vs-form-label"},Fe={class:"field field--toggle"},xe={class:"vs-form-label"},ze={class:"toggle-wrap"},Le={key:1,class:"senders-list"},Oe={class:"senders-list__title"},$e={class:"senders-grid"},Ue={dir:"ltr"},Ae={class:"notif-card__footer"},Ie={class:"admin-surface notif-card"},je={class:"notif-card__head"},De={class:"notif-card__titles"},Qe={class:"vs-card-title"},Ee={class:"vs-card-subtitle"},He={class:"notif-card__body"},Re={class:"field-grid field-grid--send"},We={class:"field"},Me={for:"dealer-select",class:"vs-form-label"},Ge={class:"dealer-option"},Je={class:"dealer-option__phone",dir:"ltr"},Ke={class:"send-all-toggle"},Xe={key:0},Ye={class:"field field--grow"},Ze={for:"message-body",class:"vs-form-label"},et={class:"notif-card__footer"},tt={class:"admin-surface notif-card"},st={class:"notif-card__head"},at={class:"notif-card__titles"},lt={class:"vs-card-title"},it={class:"vs-card-subtitle"},nt={class:"notif-card__body notif-card__body--log"},ot={key:0,class:"log-loading"},dt={key:1,class:"log-empty"},rt={key:2,class:"log-list"},ct={class:"log-item__top"},ut={class:"log-item__message"},gt={class:"log-item__meta"},ht={dir:"ltr"},pt={key:0},_t={__name:"NotificationsPage",setup(o){const{t}=J(),r=K(),p=f({configured:!1}),u=$({wa_queue_base_url:"",wa_queue_sender_id:null,wa_queue_enabled:!1}),a=$({dealer_id:null,message:"",send_to_all:!1}),q=f([]),b=f([]),_=f(null),S=f(!1),B=f(!1),C=f(!1),m=f(!1),P=F(()=>q.value.filter(n=>n.has_phone).length),z=F(()=>!p.value.configured||a.message.trim().length===0?!1:a.send_to_all?P.value>0:!!a.dealer_id),A=F(()=>a.send_to_all?t("dealerNotifications.sendToAllNow"):t("dealerNotifications.sendNow"));X(()=>a.send_to_all,n=>{n&&(a.dealer_id=null)});function I(n){return n.error_message?t("dealerNotifications.statusFailed"):n.wa_queue_status||t("dealerNotifications.statusQueued")}async function j(){const{data:n}=await w.get("/admin/wa-queue/settings"),s=n.data??{};p.value=s,u.wa_queue_base_url=s.wa_queue_base_url??"",u.wa_queue_sender_id=s.wa_queue_sender_id??null,u.wa_queue_enabled=!!s.wa_queue_enabled}async function D(){const{data:n}=await w.get("/admin/dealer-notifications/dealers");q.value=n.data??[]}async function T(){m.value=!0;try{const{data:n}=await w.get("/admin/dealer-notifications");b.value=n.data??[]}finally{m.value=!1}}async function Q(){S.value=!0;try{const{data:n}=await w.put("/admin/wa-queue/settings",{wa_queue_base_url:u.wa_queue_base_url||null,wa_queue_sender_id:u.wa_queue_sender_id||null,wa_queue_enabled:u.wa_queue_enabled});p.value=n.data??p.value,r.add({severity:"success",summary:n.message||t("dealerNotifications.saved"),life:3e3})}catch(n){r.add({severity:"error",summary:t("common.error"),detail:n.response?.data?.message||t("dealerNotifications.saveFailed"),life:4500})}finally{S.value=!1}}async function E(){B.value=!0,_.value=null;try{const{data:n}=await w.post("/admin/wa-queue/test-connection");_.value=n.data??{ok:!0,message:n.message},r.add({severity:_.value.ok?"success":"warn",summary:n.message,life:4e3})}catch(n){_.value=n.response?.data?.data??{ok:!1,message:n.response?.data?.message||t("dealerNotifications.testFailed")},r.add({severity:"error",summary:_.value.message,life:5e3})}finally{B.value=!1}}async function H(){if(z.value){C.value=!0;try{const n={message:a.message.trim(),send_to_all:a.send_to_all};a.send_to_all||(n.dealer_id=a.dealer_id);const{data:s}=await w.post("/admin/dealer-notifications/send",n);r.add({severity:s.failed>0&&s.sent>0?"warn":"success",summary:s.message,life:5e3}),a.message="",Array.isArray(s.data)&&s.data.length?b.value=[...s.data,...b.value]:s.data?b.value=[s.data,...b.value]:await T()}catch(n){const s=n.response?.data?.message||t("dealerNotifications.sendFailed");r.add({severity:"error",summary:s,detail:n.response?.data?.errors?Object.values(n.response.data.errors).flat().join(" · "):void 0,life:6e3})}finally{C.value=!1}}}return Y(async()=>{await Promise.all([j(),D(),T()])}),(n,s)=>(g(),h("div",we,[e("div",me,[e("section",ve,[e("header",ye,[s[6]||(s[6]=e("span",{class:"notif-card__icon notif-card__icon--wa"},[e("i",{class:"pi pi-whatsapp"})],-1)),e("div",ke,[e("h2",Ne,d(l(t)("dealerNotifications.waQueueTitle")),1),e("p",Ve,d(l(t)("dealerNotifications.waQueueSub")),1)]),c(l(x),{class:"notif-card__badge",severity:p.value.configured?"success":"warn",value:p.value.configured?l(t)("dealerNotifications.configured"):l(t)("dealerNotifications.notConfigured")},null,8,["severity","value"])]),e("div",qe,[e("div",Se,[s[7]||(s[7]=e("label",{for:"wa-base",class:"vs-form-label"},"WA Queue Base URL",-1)),c(l(te),{id:"wa-base",modelValue:u.wa_queue_base_url,"onUpdate:modelValue":s[0]||(s[0]=i=>u.wa_queue_base_url=i),class:"w-full",dir:"ltr",placeholder:"https://tenant.wa-queue.test/api/v1"},null,8,["modelValue"]),e("small",Be,d(l(t)("dealerNotifications.baseUrlHint")),1)]),e("div",Ce,[e("div",Pe,[e("label",Te,d(l(t)("dealerNotifications.senderId")),1),c(l(se),{id:"wa-sender",modelValue:u.wa_queue_sender_id,"onUpdate:modelValue":s[1]||(s[1]=i=>u.wa_queue_sender_id=i),class:"w-full","use-grouping":!1,"input-class":"w-full"},null,8,["modelValue"])]),e("div",Fe,[e("label",xe,d(l(t)("dealerNotifications.enable")),1),e("div",ze,[c(l(U),{modelValue:u.wa_queue_enabled,"onUpdate:modelValue":s[2]||(s[2]=i=>u.wa_queue_enabled=i)},null,8,["modelValue"])])])]),_.value?(g(),h("div",{key:0,class:y(["connection-result",_.value.ok?"connection-result--ok":"connection-result--error"])},[e("i",{class:y(["pi",_.value.ok?"pi-check-circle":"pi-times-circle"])},null,2),e("span",null,d(_.value.message),1)],2)):k("",!0),_.value?.senders?.length?(g(),h("div",Le,[e("h3",Oe,d(l(t)("dealerNotifications.senders")),1),e("div",$e,[(g(!0),h(L,null,O(_.value.senders,i=>(g(),h("div",{key:i.id,class:y(["sender-card",{"sender-card--online":i.api_connected}])},[e("strong",null,d(i.name),1),e("span",Ue,d(i.phone),1),c(l(x),{severity:i.api_connected?"success":"danger",value:i.status_label||i.status},null,8,["severity","value"])],2))),128))])])):k("",!0)]),e("footer",Ae,[c(l(N),{label:l(t)("dealerNotifications.testConnection"),icon:"pi pi-bolt",severity:"secondary",outlined:"",loading:B.value,onClick:E},null,8,["label","loading"]),c(l(N),{label:l(t)("dealerNotifications.saveSettings"),icon:"pi pi-save",loading:S.value,onClick:Q},null,8,["label","loading"])])]),e("section",Ie,[e("header",je,[s[8]||(s[8]=e("span",{class:"notif-card__icon notif-card__icon--send"},[e("i",{class:"pi pi-send"})],-1)),e("div",De,[e("h2",Qe,d(l(t)("dealerNotifications.sendTitle")),1),e("p",Ee,d(l(t)("dealerNotifications.sendSub")),1)])]),e("div",He,[e("div",Re,[e("div",We,[e("label",Me,d(l(t)("dealerNotifications.selectDealer")),1),c(l(le),{id:"dealer-select",modelValue:a.dealer_id,"onUpdate:modelValue":s[3]||(s[3]=i=>a.dealer_id=i),options:q.value,"option-label":"company_name","option-value":"id",placeholder:l(t)("dealerNotifications.selectDealerPlaceholder"),class:"w-full send-select",size:"small",filter:"",disabled:a.send_to_all},{option:Z(({option:i})=>[e("div",Ge,[e("span",null,d(i.company_name),1),e("span",Je,d(i.phone||"—"),1)])]),_:1},8,["modelValue","options","placeholder","disabled"]),e("label",Ke,[c(l(ee),{modelValue:a.send_to_all,"onUpdate:modelValue":s[4]||(s[4]=i=>a.send_to_all=i),binary:"","input-id":"send-to-all"},null,8,["modelValue"]),e("span",null,[V(d(l(t)("dealerNotifications.sendToAll"))+" ",1),P.value?(g(),h("small",Xe,"("+d(P.value)+")",1)):k("",!0)])])]),e("div",Ye,[e("label",Ze,d(l(t)("dealerNotifications.message")),1),c(l(ae),{id:"message-body",modelValue:a.message,"onUpdate:modelValue":s[5]||(s[5]=i=>a.message=i),rows:"4",class:"w-full",size:"small",placeholder:l(t)("dealerNotifications.messagePlaceholder"),"auto-resize":""},null,8,["modelValue","placeholder"])])])]),e("footer",et,[c(l(N),{label:A.value,icon:"pi pi-whatsapp",size:"small",loading:C.value,disabled:!z.value,onClick:H},null,8,["label","loading","disabled"])])]),e("section",tt,[e("header",st,[s[9]||(s[9]=e("span",{class:"notif-card__icon notif-card__icon--log"},[e("i",{class:"pi pi-list"})],-1)),e("div",at,[e("h2",lt,d(l(t)("dealerNotifications.logTitle")),1),e("p",it,d(l(t)("dealerNotifications.logSub")),1)]),c(l(N),{class:"notif-card__refresh",icon:"pi pi-refresh",text:"",rounded:"",loading:m.value,onClick:T},null,8,["loading"])]),e("div",nt,[m.value?(g(),h("div",ot,[c(l(ne),{style:{width:"32px",height:"32px"}})])):b.value.length?(g(),h("ul",rt,[(g(!0),h(L,null,O(b.value,i=>(g(),h("li",{key:i.id,class:y(["log-item",{"log-item--failed":!i.success}])},[e("div",ct,[e("strong",null,d(i.dealer_name||l(t)("notifications.dealerFallback")),1),c(l(x),{severity:i.success?"success":"danger",value:I(i)},null,8,["severity","value"])]),e("p",ut,d(i.message),1),e("div",gt,[e("span",ht,[s[11]||(s[11]=e("i",{class:"pi pi-phone"},null,-1)),V(" "+d(i.phone),1)]),e("span",null,[s[12]||(s[12]=e("i",{class:"pi pi-clock"},null,-1)),V(" "+d(l(oe)(i.created_at)),1)]),i.author_name?(g(),h("span",pt,[s[13]||(s[13]=e("i",{class:"pi pi-user"},null,-1)),V(" "+d(i.author_name),1)])):k("",!0)])],2))),128))])):(g(),h("div",dt,[s[10]||(s[10]=e("i",{class:"pi pi-inbox"},null,-1)),e("p",null,d(l(t)("dealerNotifications.logEmpty")),1)]))])])])]))}},Bt=G(_t,[["__scopeId","data-v-a0cb402d"]]);export{Bt as default};
