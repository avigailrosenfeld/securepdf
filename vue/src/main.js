import Vue from "vue"
import Vuikit from "vuikit"
import "@vuikit/theme"
import "vue-awesome/icons"
import Icon from "vue-awesome/components/Icon"
import VueRouter from "vue-router"
import { store } from "./store/index"
import notFound from "./components/NotFound"
import Paginate from "./components/Paginate"

function init(coursemoduleid, pagecount, initialPage) {
    // We need to overwrite the variable for lazy loading.
    __webpack_public_path__ = M.cfg.wwwroot + '/mod/securepdf/amd/build/';
    Vue.use(Vuikit);
    Vue.use(VueRouter);
    Vue.component("v-icon", Icon);
   
    store.commit("setCourseModuleID", coursemoduleid);
    store.dispatch("init").then(() => {
    });
    
    if(!initialPage || initialPage < 1 ){
      initialPage = 1;
    }
    
    const routes = [
        {   
            path: "/",
            redirect: { name: "Paginate-show", params: {index: 1} },
        },
        {   
          path: "/page/:index",
          name: "Paginate-show",
          component: Paginate,
          props: {pageCount: pagecount}
      },
      {
        path: "*",
        component: notFound,
      },  
    ];
  
    // base URL is /mod/securepdf/view.php/[course module id]/
    const currentUrl = window.location.pathname;
    
    const base =
        currentUrl.substr(0, currentUrl.indexOf(".php")) +
        ".php/" +
        coursemoduleid
        "/";

        const router = new VueRouter({
          mode: "history",
          routes,
          base,
      });
    
    router.beforeEach((to, from, next) => {
        // Find a translation for the title.
        if (
          Object.hasOwnProperty.call(to, "meta") &&
          Object.hasOwnProperty.call(to.meta, "title")
        ) {
          if (Object.hasOwnProperty.call(store.state.strings, to.meta.title)) {
            document.title = store.state.strings[to.meta.title];
          }
        }
        next();
      });
        
    new Vue({
        el: '#mod-securepdf-app',
        store,
        router,
      })
}
export { init };




