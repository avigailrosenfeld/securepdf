<script>
import { store } from "../store/index"
import Preloader from './Preloader.vue'
import { mapState } from "vuex"

export default ({
  //<add
  name: 'Paginate-show',
  //>
  data() {
    return {
      innerValue: 1,
      //<add
      base64: null,
      isLoading: true,
      //>
    }
  },
  //<add
  components: {
    Preloader,
  },
  //>
  props: {
    arrBase64:{
      type: Array,
      default: () => ([])
    },
    modelValue: {
      type: Number
    },
    pageCount: {
      type: Number,
      required: true
    },
    forcePage: {
      type: Number
    },
    clickHandler: {
      type: Function,
      default: () => { }
    },
    pageRange: {
      type: Number,
      default: 3
    },
    marginPages: {
      type: Number,
      default: 1
    },
    prevText: {
      type: String,
      default: 'Prev'
    },
    nextText: {
      type: String,
      default: 'Next' 
    },
    breakViewText: {
      type: String,
      default: '…'
    },
    containerClass: {
      type: String,
      default: 'pagination'
    },
    pageClass: {
      type: String,
      default: 'page-item'
    },
    pageLinkClass: {
      type: String,
      default: 'page-link'
    },
    prevClass: {
      type: String,
      default: 'page-item'
    },
    prevLinkClass: {
      type: String,
      default: 'page-link'
    },
    nextClass: {
      type: String,
      default: 'page-item'
    },
    nextLinkClass: {
      type: String,
      default: 'page-link'
    },
    breakViewClass: {
      type: String
    },
    breakViewLinkClass: {
      type: String
    },
    activeClass: {
      type: String,
      default: 'active'
    },
    disabledClass: {
      type: String,
      default: 'disabled'
    },
    noLiSurround: {
      type: Boolean,
      default: false
    },
    firstLastButton: {
      type: Boolean,
      default: false
    },
    firstButtonText: {
      type: String,
      default: 'First'
    },
    lastButtonText: {
      type: String,
      default: 'Last'
    },
    hidePrevNext: {
      type: Boolean,
      default: false
    }
  },
  //<add
  created: function () {},
  //>
  computed: {
    selected: {
      get: function () {
        return this.modelValue || this.innerValue
      },
      set: function (newValue) {
        this.innerValue = newValue
      }
    },
    //<add
    ...mapState(["strings"]),
    pageBase64: {
      get: function () {
        return this.base64
      },
      set: function (newValue) {
        if (this.selected ){
          this.base64 = newValue
        }
      }
    },
    checkIsLoading:{
      get: function () {
        return this.isLoading
      },
      set: function (newValue) {
          this.isLoading = newValue
      }
    },
    //>
    pages: function () {
      let items = {}
      if (this.pageCount <= this.pageRange) {
        for (let index = 0; index < this.pageCount; index++) {
          let page = {
            index: index,
            content: index + 1,
            selected: index === (this.selected - 1)
          }
          items[index] = page
        }
      } else {
        const halfPageRange = Math.floor(this.pageRange / 2)

        let setPageItem = index => {
          let page = {
            index: index,
            content: index + 1,
            selected: index === (this.selected - 1)
          }

          items[index] = page
        }

        let setBreakView = index => {
          let breakView = {
            disabled: true,
            breakView: true
          }

          items[index] = breakView
        }

        // 1st - loop thru low end of margin pages
        for (let i = 0; i < this.marginPages; i++) {
          setPageItem(i);
        }

        // 2nd - loop thru selected range
        let selectedRangeLow = 0;
        if (this.selected - halfPageRange > 0) {
          selectedRangeLow = this.selected - 1 - halfPageRange;
        }

        let selectedRangeHigh = selectedRangeLow + this.pageRange - 1;
        if (selectedRangeHigh >= this.pageCount) {
          selectedRangeHigh = this.pageCount - 1;
          selectedRangeLow = selectedRangeHigh - this.pageRange + 1;
        }

        for (let i = selectedRangeLow; i <= selectedRangeHigh && i <= this.pageCount - 1; i++) {
          setPageItem(i);
        }

        // Check if there is breakView in the left of selected range
        if (selectedRangeLow > this.marginPages) {
          setBreakView(selectedRangeLow - 1)
        }

        // Check if there is breakView in the right of selected range
        if (selectedRangeHigh + 1 < this.pageCount - this.marginPages) {
          setBreakView(selectedRangeHigh + 1)
        }

        // 3rd - loop thru high end of margin pages
        for (let i = this.pageCount - 1; i >= this.pageCount - this.marginPages; i--) {
          setPageItem(i);
        }
      }
      return items
    }
  },
  //<add
  watch: {
    '$route.params.index': {
      handler: 'fetchImage',
      immediate: true
    }
  },
  //>
  methods: {
    //<add 
    async fetchImage(index) {
      if (index in this.arrBase64){
        this.innerValue = index
        await this.$emit('update:modelValue', index)
        this.$store.state.page = index
        this.base64 = this.arrBase64[index]
      } else {
        this.isLoading = true;
        this.innerValue = index
        await this.$emit('update:modelValue', index)
        this.$store.state.page = index
        Promise.all([this.$store.dispatch("init").then(() => {
          this.base64 = store.getters.getBase64
          this.isLoading = false
          this.arrBase64[index] = this.base64
        })]);
      }
    },
    async changeRouter(selected) {
      this.$router.push({
          name: "Paginate-show",
          params: { index: selected },
        });
    },
    //>
    async handlePageSelected(selected) {
      if (this.selected === selected) return
      //<add
      await this.changeRouter(selected)
      //>
    },
    prevPage() {
      if (this.selected <= 1) return

      this.handlePageSelected(this.selected - 1)
    },
    nextPage() {
      if (this.selected >= this.pageCount) return

      this.handlePageSelected(this.selected + 1)
    },
    firstPageSelected() {
      return this.selected === 1
    },
    lastPageSelected() {
      return (this.selected === this.pageCount) || (this.pageCount === 0)
    },
    selectFirstPage() {
      if (this.selected <= 1) return

      this.handlePageSelected(1)
    },
    selectLastPage() {
      if (this.selected >= this.pageCount) return

      this.handlePageSelected(this.pageCount)
    },
    beforeMount() {
      this.innerValue = this.$route.params.index
    },
    beforeUpdate() {
      if (this.forcePage === undefined) return
      if (this.forcePage !== this.selected) {
          this.selected = this.forcePage
      }
    },
  },
})
</script>

<template>
<!--add-->
<div  v-if="!noLiSurround">
<!-- end add-->
  <ul  :class="containerClass">
    <li v-if="firstLastButton" :class="[pageClass, firstPageSelected() ? disabledClass : '']">
      <a
        @click="selectFirstPage()"
        @keyup.enter="selectFirstPage()"
        :class="pageLinkClass"
        :tabindex="firstPageSelected() ? -1 : 0"
        v-html="firstButtonText"
      ></a>
    </li>

    <li
      v-if="!(firstPageSelected() && hidePrevNext)"
      :class="[prevClass, firstPageSelected() ? disabledClass : '']"
    >
      <a v-if="strings.prev"
        @click="prevPage()"
        @keyup.enter="prevPage()"
        :class="prevLinkClass"
        :tabindex="firstPageSelected() ? -1 : 0"
        v-html='strings.prev'
      ></a>
    </li>

    <li
      v-for="page in pages"
      :key="page.index"
      :class="[pageClass, page.selected ? activeClass : '', page.disabled ? disabledClass : '', page.breakView ? breakViewClass : '']"
    >
    
      <a v-if="page.breakView" :class="[pageLinkClass, breakViewLinkClass]" tabindex="0">
        <slot name="breakViewContent">{{ breakViewText }}</slot>
      </a>
      <a v-else-if="page.disabled" :class="pageLinkClass" tabindex="0">{{ page.content }}</a>
      <a
        v-else
        @click="handlePageSelected(page.index + 1)"
        @keyup.enter="handlePageSelected(page.index + 1)"
        :class="pageLinkClass"
        tabindex="0"
      >{{ page.content }}</a>
    </li>
    <li
      v-if="!(lastPageSelected() && hidePrevNext)"
      :class="[nextClass, lastPageSelected() ? disabledClass : '']"
    >
      <a v-if="strings.next"
        @click="nextPage()"
        @keyup.enter="nextPage()"
        :class="nextLinkClass"
        :tabindex="lastPageSelected() ? -1 : 0"
        v-html= 'strings.next'
      ></a>
    </li>

    <li v-if="firstLastButton" :class="[pageClass, lastPageSelected() ? disabledClass : '']">
      <a
        @click="selectLastPage()"
        @keyup.enter="selectLastPage()"
        :class="pageLinkClass"
        :tabindex="lastPageSelected() ? -1 : 0"
        v-html="lastButtonText"
      ></a>
    </li>

  </ul>
<!--add-->
  <div v-if="this.isLoading">
    <Preloader  color="#0f6cbf"/>
  </div>
  <div v-else>
    <img :src="`data:image/png;base64,${this.base64}`" :alt="`Page ${this.innerValue}`" galleryimg="no" onContextMenu="return false;">
  </div>
</div>
<!-- end add-->
  <div :class="containerClass" v-else>
    <a
      v-if="firstLastButton"
      @click="selectFirstPage()"
      @keyup.enter="selectFirstPage()"
      :class="[pageLinkClass, firstPageSelected() ? disabledClass : '']"
      tabindex="0"
      v-html="firstButtonText"
    ></a>
    <a
      v-if="!(firstPageSelected() && hidePrevNext)"
      @click="prevPage()"
      @keyup.enter="prevPage()"
      :class="[prevLinkClass, firstPageSelected() ? disabledClass : '']"
      tabindex="0"
      v-html="prevText"
    ></a>

    <template v-for="page in pages">
      <a
        v-if="page.breakView"
        :key="page.index"
        :class="[pageLinkClass, breakViewLinkClass, page.disabled ? disabledClass : '']"
        tabindex="0"
      >
        <slot name="breakViewContent">{{ breakViewText }}</slot>
      </a>
    </template>
    <a
      v-if="!(lastPageSelected() && hidePrevNext)"
      @click="nextPage()"
      @keyup.enter="nextPage()"
      :class="[nextLinkClass, lastPageSelected() ? disabledClass : '']"
      tabindex="0"
      v-html="nextText"
    ></a>
    <a
      v-if="firstLastButton"
      @click="selectLastPage()"
      @keyup.enter="selectLastPage()"
      :class="[pageLinkClass, lastPageSelected() ? disabledClass : '']"
      tabindex="0"
      v-html="lastButtonText"
    ></a>
  </div>  

</template>

<style scoped>
/*add*/
.page-link:focus {
   
    box-shadow: none;
}
</style>