import React from "react";
import "./index.css";
import NavBar from "./components/nav/NavBar";
import { Route, Routes } from "react-router-dom";
import Home from "./components/pages/Home";
import Cart from "./components/pages/Cart";
import Single from "./components/pages/Single";
import PrivateRoute from "./components/PrivateRoute";
import Login from "./components/nav/Login";
import { useEffect } from "react";
import { setCartNumbers } from "./features/cart/cartSlice";
import { useSelector, useDispatch } from "react-redux";

function App() {
  const dispatch = useDispatch();
  const { cartItems } = useSelector((state) => state.cart);
  useEffect(() => {
    dispatch(setCartNumbers());
  }, [cartItems]);
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
