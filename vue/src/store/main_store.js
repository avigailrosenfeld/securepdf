import moodleAjax from "core/ajax";
import moodleStorage from "core/localstorage";
import $ from "jquery";
import { ajax } from "./index";


export default {
  state: {
    initialized: false,
    now: new Date(),
    lang: null,
    courseModuleID: 0,
    contextID: 0,
    strings: {},
    base64: null,
    page: 1,
  },
  mutations: {
    updateTime(state) {
      state.now = new Date();
    },
    setInitialized(state, initialized) {
      state.initialized = initialized;
    },
    setLang(state, lang) {
      state.lang = lang;
    },
    setCourseModuleID(state, id) {
      state.courseModuleID = id;
    },
    setContextID(state, id) {
      state.contextID = id;
    },
    setStrings(state, strings) {
      state.strings = strings;
    },
    setBase64(state, base64) {
      state.base64 = base64;
    },
    setPage(state, page) {
      state.page = page;
    },
  },
  getters: {
    isInitialized: (state, getters) => {
      if (!state.initialized) {
        return false;
      }
    },
    getBase64: (state) => {
      return state.base64;
    },
  },
  actions: {
    /**
     * Initializes everything (load language, strings, game).
     *
     * @param context
     */
    async init(context) {
      return Promise.all([
        context.dispatch("startTimeTracking"),
        context.dispatch("loadLang").then(() => {
          return Promise.all([
            context.dispatch("loadComponentStrings"),
            context.dispatch("fetchBase64"),
          ]).then(() => {
            context.commit("setInitialized", true);
          });
        }),
      ]);
    },
    /**
     * We need a reactive current time.
     *
     * @param context
     */
    async startTimeTracking(context) {
      setInterval(() => {
        context.commit("updateTime");
      }, 1000);
    },
    /**
     * Determines the current language.
     *
     * @param context
     *
     * @returns {Promise<void>}
     */
    async loadLang(context) {
      const lang = $("html").attr("lang").replace(/-/g, "_");
      context.commit("setLang", lang);
    },
    /**
     * Fetches the i18n data for the current language.
     *
     * @param context
     * @returns {Promise<void>}
     */
    async loadComponentStrings(context) {
      let lang = this.state.lang;
      const cacheKey = "mod_securepdf/strings/" + lang;
      const cachedStrings = moodleStorage.get(cacheKey);
      if (cachedStrings) {
        context.commit("setStrings", JSON.parse(cachedStrings));
      } else {
        const request = {
          methodname: "core_get_component_strings",
          args: {
            component: "mod_securepdf",
            lang,
          },
        };
        const loadedStrings = await moodleAjax.call([request])[0];
        let strings = {};
        loadedStrings.forEach((s) => {
          strings[s.stringid] = s.string;
        });
        context.commit("setStrings", strings);
        moodleStorage.set(cacheKey, JSON.stringify(strings));
      }
    },
    /**
     * Fetches game options and active user info.
     *
     * @param context
     * @returns {Promise<void>}
     */
    async fetchBase64(context) {
      const base64 = await ajax("mod_securepdf_get_pages");
        //add to map TODO
      context.commit("setBase64", base64['base64']);
      context.commit("setPage", base64['initialpage']);
    },
  },
};