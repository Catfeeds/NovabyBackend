webpackJsonp([35],{249:function(t,e,s){var i=s(6)(s(495),s(691),null,null);t.exports=i.exports},495:function(t,e,s){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var i=s(66),r=s.n(i);e.default={name:"guide",data:function(){return{userID:[],artistsList:[]}},components:{peoplePortrait:r.a},mounted:function(){var t=this;this.$store.dispatch("getRecommendlist").then(function(e){t.artistsList=e,t.artistsList.forEach(function(t,e){t.checked=!0})})},methods:{fllow:function(){var t=this;this.artistsList.forEach(function(e,s){1==e.checked&&t.userID.push(e.user_id)});var e={users:this.userID};this.$store.dispatch("followUser",e).then(function(e){200==e.code&&t.$router.push({path:"/models"})})}}}},691:function(t,e){t.exports={render:function(){var t=this,e=t.$createElement,s=t._self._c||e;return s("section",[s("div",{staticClass:"main"},[s("div",{staticClass:"guide-box auto position-relative"},[s("h1",{staticClass:"text-center"},[t._v("Meet with great 3D artists")]),t._v(" "),s("form",{staticClass:"clearfix artists-list"},[s("router-link",{staticClass:"float-left",attrs:{to:"/home"}},[t._v("Skip")]),t._v(" "),s("button",{staticClass:"btn btn-default float-right",attrs:{type:"button"},on:{click:t.fllow}},[t._v("Follow")]),t._v(" "),s("ul",{staticClass:"float-left"},t._l(t.artistsList,function(e){return s("li",[s("div",[s("people-portrait",{attrs:{portraitUrl:e.user_avatar,type:e.user_type,width:16,height:16}})],1),t._v(" "),s("div",[s("p",[t._v(t._s(e.user_name))]),t._v(" "),s("span",[t._v(t._s(e.user_job)+"/"+t._s(e.user_country))])]),t._v(" "),s("label",[s("input",{directives:[{name:"model",rawName:"v-model",value:e.checked,expression:"item.checked"}],attrs:{type:"checkbox"},domProps:{checked:Array.isArray(e.checked)?t._i(e.checked,null)>-1:e.checked},on:{__c:function(s){var i=e.checked,r=s.target,a=!!r.checked;if(Array.isArray(i)){var c=t._i(i,null);a?c<0&&(e.checked=i.concat(null)):c>-1&&(e.checked=i.slice(0,c).concat(i.slice(c+1)))}else e.checked=a}}})])])}))],1),t._v(" "),s("div",{staticClass:"bottom"})])])])},staticRenderFns:[]}}});
//# sourceMappingURL=35.1209cc420a476cbc0253.js.map