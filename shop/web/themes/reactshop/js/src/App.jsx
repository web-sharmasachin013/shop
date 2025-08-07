import React from "react";
import "./index.css";
import NavBar from "./components/nav/NavBar";
import { Route, Routes } from "react-router-dom";
import Home from "./components/pages/Home";
import Cart from "./components/pages/Cart";
import Single from "./components/pages/Single";
import Login from "./components/nav/Login";
import { useEffect, useRef, useState } from "react";
import { setCartNumbers, viewCartItems } from "./features/cart/cartSlice";
import { useSelector, useDispatch } from "react-redux";
import { fetchProducts } from "./features/product/productSlice";
import { fetchCart } from "./features/cart/cartSlice";
import { selectEnrichedCartItems } from "./selectors/cartSelectors";

function App() {
  const dispatch = useDispatch();
  const cartItemsProducts = useSelector(selectEnrichedCartItems);
  const hasRun = useRef(false); // ✅ flag to prevent infinite loop
  const { cartItems } = useSelector((state) => state.cart);
  const [count, setcount] = useState(true); // boolean: true or false
  console.log(cartItems);

  useEffect(() => {
    dispatch(setCartNumbers());
  }, [cartItems]);

  useEffect(() => {
    dispatch(fetchProducts());
    dispatch(fetchCart());
  }, [dispatch]);

  useEffect(() => {
    if (!hasRun.current && cartItemsProducts.length > 0) {
      hasRun.current = true; // set flag BEFORE calling the function
      //dispatch(viewCartItems(cartItemsProducts)); // ✅ safe to call state setters here
    }
  }, [cartItemsProducts, cartItems]);

  return (
    <div className="wrapper bg-dark text-white">
      <NavBar />
      <div className="container mt-5 py-5 px-3 px-md-5">
        <Routes>
          <Route path="/" element={<Home />}></Route>
          <Route path="/login" element={<Login />} />
          <Route path="/cart" element={<Cart />}></Route>
          <Route path="/single/:id" element={<Single />}></Route>
          <Route path="/login" element={<Login />} />
        </Routes>
      </div>
    </div>
  );
}

export default App;
