import{B as j,N as A,o as h,l as p,g as e,y as v,x as E,u as H,b as R,k as G,t as d,f as s,e as u,s as y,n as k,m as B,F as T,q as x,w as M,G as w,r as f,a7 as L,H as W}from"./app-C_F93Ysb.js";import{s as J}from"./index-BBuE3nTd.js";import{a as K,b as X}from"./index-CgxZsJUK.js";import{s as Y}from"./index-PUI8niNv.js";import{a as Z}from"./index-DDpKCEg4.js";import{s as C}from"./index-D8QV2oeJ.js";import{s as ee}from"./index-DGKZBzBg.js";import{f as te}from"./formatDateTime-BLTbgLs_.js";import{_ as se}from"./_plugin-vue_export-helper-DlAUqK2U.js";import"./index-DW_JA6TA.js";var ae=`
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
`,le={root:{position:"relative"}},ie={root:function(t){var r=t.instance,g=t.props;return["p-toggleswitch p-component",{"p-toggleswitch-checked":r.checked,"p-disabled":g.disabled,"p-invalid":r.$invalid}]},input:"p-toggleswitch-input",slider:"p-toggleswitch-slider",handle:"p-toggleswitch-handle"},ne=j.extend({name:"toggleswitch",style:ae,classes:ie,inlineStyles:le}),oe={name:"BaseToggleSwitch",extends:Z,props:{trueValue:{type:null,default:!0},falseValue:{type:null,default:!1},readonly:{type:Boolean,default:!1},tabindex:{type:Number,default:null},inputId:{type:String,default:null},inputClass:{type:[String,Object],default:null},inputStyle:{type:Object,default:null},ariaLabelledby:{type:String,default:null},ariaLabel:{type:String,default:null}},style:ne,provide:function(){return{$pcToggleSwitch:this,$parentInstance:this}}},O={name:"ToggleSwitch",extends:oe,inheritAttrs:!1,emits:["change","focus","blur"],methods:{getPTOptions:function(t){var r=t==="root"?this.ptmi:this.ptm;return r(t,{context:{checked:this.checked,disabled:this.disabled}})},onChange:function(t){if(!this.disabled&&!this.readonly){var r=this.checked?this.falseValue:this.trueValue;this.writeValue(r,t),this.$emit("change",t)}},onFocus:function(t){this.$emit("focus",t)},onBlur:function(t){var r,g;this.$emit("blur",t),(r=(g=this.formField).onBlur)===null||r===void 0||r.call(g,t)}},computed:{checked:function(){return this.d_value===this.trueValue},dataP:function(){return A({checked:this.checked,disabled:this.disabled,invalid:this.$invalid})}}},de=["data-p-checked","data-p-disabled","data-p"],re=["id","checked","tabindex","disabled","readonly","aria-checked","aria-labelledby","aria-label","aria-invalid"],ce=["data-p"],ue=["data-p"];function ge(n,t,r,g,c,l){return h(),p("div",v({class:n.cx("root"),style:n.sx("root")},l.getPTOptions("root"),{"data-p-checked":l.checked,"data-p-disabled":n.disabled,"data-p":l.dataP}),[e("input",v({id:n.inputId,type:"checkbox",role:"switch",class:[n.cx("input"),n.inputClass],style:n.inputStyle,checked:l.checked,tabindex:n.tabindex,disabled:n.disabled,readonly:n.readonly,"aria-checked":l.checked,"aria-labelledby":n.ariaLabelledby,"aria-label":n.ariaLabel,"aria-invalid":n.invalid||void 0,onFocus:t[0]||(t[0]=function(){return l.onFocus&&l.onFocus.apply(l,arguments)}),onBlur:t[1]||(t[1]=function(){return l.onBlur&&l.onBlur.apply(l,arguments)}),onChange:t[2]||(t[2]=function(){return l.onChange&&l.onChange.apply(l,arguments)})},l.getPTOptions("input")),null,16,re),e("div",v({class:n.cx("slider")},l.getPTOptions("slider"),{"data-p":l.dataP}),[e("div",v({class:n.cx("handle")},l.getPTOptions("handle"),{"data-p":l.dataP}),[E(n.$slots,"handle",{checked:l.checked})],16,ue)],16,ce)],16,de)}O.render=ge;const he={class:"admin-page dealer-notifications-page"},pe={class:"notifications-grid"},_e={class:"admin-surface settings-card settings-card--wide"},fe={class:"settings-card__head"},we={class:"vs-card-title"},be={class:"vs-card-subtitle"},me={class:"settings-card__body"},ve={class:"field"},ye={class:"field-hint"},ke={class:"field-row"},Ne={class:"field"},qe={for:"wa-sender",class:"vs-form-label"},Se={class:"field field--toggle"},Ve={class:"vs-form-label"},Be={class:"actions-row"},Ce={key:1,class:"senders-list"},Pe={class:"senders-list__title"},Fe={dir:"ltr"},Te={class:"admin-surface settings-card"},xe={class:"settings-card__head"},Le={class:"vs-card-title"},Oe={class:"vs-card-subtitle"},$e={class:"settings-card__body"},ze={class:"field"},Ue={for:"dealer-select",class:"vs-form-label"},Ie={class:"dealer-option"},De={class:"dealer-option__phone",dir:"ltr"},Qe={class:"field"},je={for:"message-body",class:"vs-form-label"},Ae={class:"admin-surface settings-card settings-card--wide"},Ee={class:"settings-card__head"},He={class:"vs-card-title"},Re={class:"vs-card-subtitle"},Ge={class:"settings-card__body"},Me={key:0,class:"log-loading"},We={key:1,class:"log-empty"},Je={key:2,class:"log-list"},Ke={class:"log-item__top"},Xe={class:"log-item__message"},Ye={class:"log-item__meta"},Ze={dir:"ltr"},et={key:0},tt={__name:"NotificationsPage",setup(n){const{t}=H(),r=R(),g=f({configured:!1}),c=L({wa_queue_base_url:"",wa_queue_sender_id:null,wa_queue_enabled:!1}),l=L({dealer_id:null,message:""}),P=f([]),b=f([]),_=f(null),N=f(!1),q=f(!1),S=f(!1),m=f(!1),F=W(()=>!!l.dealer_id&&l.message.trim().length>0&&g.value.configured);function $(i){return i.error_message?t("dealerNotifications.statusFailed"):i.wa_queue_status||t("dealerNotifications.statusQueued")}async function z(){const{data:i}=await w.get("/admin/wa-queue/settings"),o=i.data??{};g.value=o,c.wa_queue_base_url=o.wa_queue_base_url??"",c.wa_queue_sender_id=o.wa_queue_sender_id??null,c.wa_queue_enabled=!!o.wa_queue_enabled}async function U(){const{data:i}=await w.get("/admin/dealer-notifications/dealers");P.value=i.data??[]}async function V(){m.value=!0;try{const{data:i}=await w.get("/admin/dealer-notifications");b.value=i.data??[]}finally{m.value=!1}}async function I(){N.value=!0;try{const{data:i}=await w.put("/admin/wa-queue/settings",{wa_queue_base_url:c.wa_queue_base_url||null,wa_queue_sender_id:c.wa_queue_sender_id||null,wa_queue_enabled:c.wa_queue_enabled});g.value=i.data??g.value,r.add({severity:"success",summary:i.message||t("dealerNotifications.saved"),life:3e3})}catch(i){r.add({severity:"error",summary:t("common.error"),detail:i.response?.data?.message||t("dealerNotifications.saveFailed"),life:4500})}finally{N.value=!1}}async function D(){q.value=!0,_.value=null;try{const{data:i}=await w.post("/admin/wa-queue/test-connection");_.value=i.data??{ok:!0,message:i.message},r.add({severity:_.value.ok?"success":"warn",summary:i.message,life:4e3})}catch(i){_.value=i.response?.data?.data??{ok:!1,message:i.response?.data?.message||t("dealerNotifications.testFailed")},r.add({severity:"error",summary:_.value.message,life:5e3})}finally{q.value=!1}}async function Q(){if(F.value){S.value=!0;try{const{data:i}=await w.post("/admin/dealer-notifications/send",{dealer_id:l.dealer_id,message:l.message.trim()});r.add({severity:"success",summary:i.message,life:4e3}),l.message="",i.data?b.value=[i.data,...b.value]:await V()}catch(i){r.add({severity:"error",summary:t("common.error"),detail:i.response?.data?.message||t("dealerNotifications.sendFailed"),life:5e3})}finally{S.value=!1}}}return G(async()=>{await Promise.all([z(),U(),V()])}),(i,o)=>(h(),p("div",he,[e("div",pe,[e("section",_e,[e("header",fe,[o[5]||(o[5]=e("i",{class:"pi pi-whatsapp"},null,-1)),e("div",null,[e("h2",we,d(s(t)("dealerNotifications.waQueueTitle")),1),e("p",be,d(s(t)("dealerNotifications.waQueueSub")),1)]),u(s(C),{severity:g.value.configured?"success":"warn",value:g.value.configured?s(t)("dealerNotifications.configured"):s(t)("dealerNotifications.notConfigured")},null,8,["severity","value"])]),e("div",me,[e("div",ve,[o[6]||(o[6]=e("label",{for:"wa-base",class:"vs-form-label"},"WA Queue Base URL",-1)),u(s(J),{id:"wa-base",modelValue:c.wa_queue_base_url,"onUpdate:modelValue":o[0]||(o[0]=a=>c.wa_queue_base_url=a),class:"w-full",dir:"ltr",placeholder:"https://tenant.wa-queue.test/api/v1"},null,8,["modelValue"]),e("small",ye,d(s(t)("dealerNotifications.baseUrlHint")),1)]),e("div",ke,[e("div",Ne,[e("label",qe,d(s(t)("dealerNotifications.senderId")),1),u(s(K),{id:"wa-sender",modelValue:c.wa_queue_sender_id,"onUpdate:modelValue":o[1]||(o[1]=a=>c.wa_queue_sender_id=a),class:"w-full","use-grouping":!1,"input-class":"w-full"},null,8,["modelValue"])]),e("div",Se,[e("label",Ve,d(s(t)("dealerNotifications.enable")),1),u(s(O),{modelValue:c.wa_queue_enabled,"onUpdate:modelValue":o[2]||(o[2]=a=>c.wa_queue_enabled=a)},null,8,["modelValue"])])]),e("div",Be,[u(s(y),{label:s(t)("dealerNotifications.saveSettings"),icon:"pi pi-save",loading:N.value,onClick:I},null,8,["label","loading"]),u(s(y),{label:s(t)("dealerNotifications.testConnection"),icon:"pi pi-bolt",severity:"secondary",outlined:"",loading:q.value,onClick:D},null,8,["label","loading"])]),_.value?(h(),p("div",{key:0,class:k(["connection-result",_.value.ok?"connection-result--ok":"connection-result--error"])},[e("i",{class:k(["pi",_.value.ok?"pi-check-circle":"pi-times-circle"])},null,2),e("span",null,d(_.value.message),1)],2)):B("",!0),_.value?.senders?.length?(h(),p("div",Ce,[e("h3",Pe,d(s(t)("dealerNotifications.senders")),1),(h(!0),p(T,null,x(_.value.senders,a=>(h(),p("div",{key:a.id,class:k(["sender-card",{"sender-card--online":a.api_connected}])},[e("strong",null,d(a.name),1),e("span",Fe,d(a.phone),1),u(s(C),{severity:a.api_connected?"success":"danger",value:a.status_label||a.status},null,8,["severity","value"])],2))),128))])):B("",!0)])]),e("section",Te,[e("header",xe,[o[7]||(o[7]=e("i",{class:"pi pi-send"},null,-1)),e("div",null,[e("h2",Le,d(s(t)("dealerNotifications.sendTitle")),1),e("p",Oe,d(s(t)("dealerNotifications.sendSub")),1)])]),e("div",$e,[e("div",ze,[e("label",Ue,d(s(t)("dealerNotifications.selectDealer")),1),u(s(X),{id:"dealer-select",modelValue:l.dealer_id,"onUpdate:modelValue":o[3]||(o[3]=a=>l.dealer_id=a),options:P.value,"option-label":"company_name","option-value":"id",placeholder:s(t)("dealerNotifications.selectDealerPlaceholder"),class:"w-full",filter:""},{option:M(({option:a})=>[e("div",Ie,[e("span",null,d(a.company_name),1),e("span",De,d(a.phone||"—"),1)])]),_:1},8,["modelValue","options","placeholder"])]),e("div",Qe,[e("label",je,d(s(t)("dealerNotifications.message")),1),u(s(Y),{id:"message-body",modelValue:l.message,"onUpdate:modelValue":o[4]||(o[4]=a=>l.message=a),rows:"5",class:"w-full",placeholder:s(t)("dealerNotifications.messagePlaceholder"),"auto-resize":""},null,8,["modelValue","placeholder"])]),u(s(y),{label:s(t)("dealerNotifications.sendNow"),icon:"pi pi-whatsapp",loading:S.value,disabled:!F.value,onClick:Q},null,8,["label","loading","disabled"])])]),e("section",Ae,[e("header",Ee,[o[8]||(o[8]=e("i",{class:"pi pi-list"},null,-1)),e("div",null,[e("h2",He,d(s(t)("dealerNotifications.logTitle")),1),e("p",Re,d(s(t)("dealerNotifications.logSub")),1)]),u(s(y),{icon:"pi pi-refresh",text:"",rounded:"",loading:m.value,onClick:V},null,8,["loading"])]),e("div",Ge,[m.value?(h(),p("div",Me,[u(s(ee),{style:{width:"28px",height:"28px"}})])):b.value.length?(h(),p("ul",Je,[(h(!0),p(T,null,x(b.value,a=>(h(),p("li",{key:a.id,class:k(["log-item",{"log-item--failed":!a.success}])},[e("div",Ke,[e("strong",null,d(a.dealer_name||s(t)("notifications.dealerFallback")),1),u(s(C),{severity:a.success?"success":"danger",value:$(a)},null,8,["severity","value"])]),e("p",Xe,d(a.message),1),e("div",Ye,[e("span",Ze,d(a.phone),1),e("span",null,d(s(te)(a.created_at)),1),a.author_name?(h(),p("span",et,d(a.author_name),1)):B("",!0)])],2))),128))])):(h(),p("p",We,d(s(t)("dealerNotifications.logEmpty")),1))])])])]))}},gt=se(tt,[["__scopeId","data-v-2080325a"]]);export{gt as default};
