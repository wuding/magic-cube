# MagicCube - Module & Controller

2.250109

## 实现过程

HTTP 请求方法和请求地址转换标准化后的句柄



## 路由处理结果

- 状态：0 未找到 1 成功匹配 2 方法不允许
- 方法：全转为小写
- 标识：<method>:<module>/<controller>@<action>



## 控制器路径和方法调用

- 模块文件夹：是否放 module 目录里

- 方法文件夹

- 方法附加到动作名：

  ```
  <prefix><action><suffix>
  ```

- 方法地图：

  ```
  $actionMaps = [
      'actionName' => [
          'get' => 'index',
          'post' => 'create',
      ],
  ];
  ```



## 预置动作

- 未找到：_notfound
- 缺省：_default


## 步骤

分割 /

片段类型 int double str nul

匹配规则

~[a-z]~

~[a-z]~i

[a-z\-\.]

_

\s

\w

非英文

| gettype           | is_       |      |
| ----------------- | --------- | ---- |
| array             |           |      |
| boolean           | bool      |      |
|                   | callable  |      |
|                   | countable |      |
| double            | 别名      |      |
|                   | float     |      |
| integer           | 别名      |      |
|                   | int       |      |
|                   | iterable  |      |
| NULL              | null      |      |
|                   | numeric   |      |
| object            |           |      |
| resource          |           |      |
| resource (closed) | -         |      |
|                   | scalar    |      |
| string            |           |      |
| unknown type      | -         |      |
