import { createSlice } from "@reduxjs/toolkit";
import data from "../../data";
import { uniq, sortBy } from "lodash";
import { stringSimilarity as getScore } from "string-similarity-js";

const categories = uniq(data.map((product) => product.category)).sort();
const DEFAULT_CATEGORY = "All";

const initialState = {
  products: data,
  productsFromSearch: data,
  categories: [DEFAULT_CATEGORY, ...categories],
  selectedCategory: DEFAULT_CATEGORY,
  searchTerm: "",
  single: data[0],
  singleSimlarProducts: [],
};

export const productSlice = createSlice({
  name: "products",
  initialState,
  reducers: {
    setSearchTerm: (state, action) => {
      let { payload: searchTerm } = action;
      state.searchTerm = searchTerm;
      state.productsFromSearch = state.products;
      if (state.searchTerm.length > 0) {
        state.productsFromSearch.forEach((p) => {
          p.simScore = getScore(`${p.name} ${p.category}`, searchTerm);
        });
        state.productsFromSearch = sortBy(
          state.productsFromSearch,
          "simScore"
        ).reverse();
      }
    },
    setSelectCategory: (state, action) => {
      let { payload: selectedCategory } = action;

      state.searchTerm = "";
      state.selectedCategory = selectedCategory;
      if (state.selectedCategory === DEFAULT_CATEGORY) {
        state.productsFromSearch = state.products;
      } else {
        state.productsFromSearch = state.products.filter((p) => {
          return p.category === state.selectedCategory;
        });
      }
    },
    setSingleProduct: (state, action) => {
      let { payload: id } = action;
      state.single = state.products.find((p) => p.id === +id);
      state.singleSimlarProducts = state.products.filter((p) => {
        return p.category === state.single.category && p.id !== state.single.id;
      });
    },
  },
});

export const { setSearchTerm, setSelectCategory, setSingleProduct } =
  productSlice.actions;
export default productSlice.reducer;
