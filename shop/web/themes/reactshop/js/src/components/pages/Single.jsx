import React, { useState, useEffect } from "react";
import { useParams } from "react-router-dom";
import { useSelector, useDispatch } from "react-redux";
import { setSingleProduct } from "../../features/product/productSlice";
import Line from "../extra/Line";

import Price from "../extra/Price";
import ProductButton from "../products/ProductButton";
import singleSimlarProducts from "../../features/product/productSlice";
import Products from "../products/Products";

function Single() {
  const { id } = useParams();

  const imgPath = "/images/" + id + ".jpg";
  const { single, singleSimlarProducts } = useSelector(
    (state) => state.products
  );

  const dispatch = useDispatch();

  useEffect(() => {
    dispatch(setSingleProduct(id));
  }, [id]);

  return (
    <div
      id="single"
      className="row justify-content-center align-items-center text-white mx-auto"
    >
      <div className="col-md-6">
        <img
          src={imgPath}
          alt=""
          className="card-img-top mb-5 mb-md-0 p-0 p-lg-5"
        />
      </div>
      <div className="col-md-6 text-center text-md-start">
        <h2 className="fs-1 fw-bold">{single.name}</h2>
        <div className="fs-5 mb-2">
          <Price value={single.price} decimals={2} />
        </div>
        <p className="lead">{single.description}</p>
        <ProductButton product={single} />
      </div>
      <Line />
      <h2 className="text-white my-4 text-center">
        Similar Products Like This
      </h2>
      <Products products={singleSimlarProducts} />
    </div>
  );
}

export default Single;
